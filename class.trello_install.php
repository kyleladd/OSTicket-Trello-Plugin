<?php

require_once 'class.setup.php';
class TrelloInstaller extends SetupWizard {
	
	/**
	 * Loads, checks and installs SQL file.
	 *
	 * @return boolean
	 */
	function install() {
		$schemaFile = TRELLO_PLUGIN_ROOT . 'install/sql/install_trello.sql'; // DB dump.
		return $this->runJob ( $schemaFile );
	}

	private function runJob($schemaFile, $show_sql_errors = true) {
		// Last minute checks.
		if (! file_exists ( $schemaFile )) {
			echo '<br />';
			var_dump ( $schemaFile );
			echo '<br />';
			echo 'File Access Error - please make sure your download is the latest (#1)';
			echo '<br />';
			$this->error = 'File Access Error!';
			return false;
		} elseif (! $this->load_sql_file ( $schemaFile, TABLE_PREFIX, true, true )) {
			if ($show_sql_errors) {
				echo '<br />';
				echo 'Error parsing SQL schema! Get help from developers (#4)';
				echo '<br />';
				return false;
			}
			return true;
		}
		
		return true;
	}
	function remove() {
		$schemaFile = TRELLO_PLUGIN_ROOT . 'install/sql/remove_trello.sql'; // DB dump.
		return $this->runJob ( $schemaFile );
	}
	function purgeData() {
		$schemaFile = TRELLO_PLUGIN_ROOT . 'install/sql/purge_trello_data.sql'; // DB dump.
		return $this->runJob ( $schemaFile );
	}
	
	/**
	 * Overriding split, we need semicolons in procedures and triggers, so
	 * the dollar sign is used instead.
	 *
	 * @param type $schema        	
	 * @param type $prefix        	
	 * @param type $abort        	
	 * @param type $debug        	
	 * @return boolean
	 */
	function load_sql($schema, $prefix, $abort = true, $debug = false) {
		
		// Strip comments and remarks
		$schema = preg_replace ( '%^\s*(#|--).*$%m', '', $schema );
		// Replace table prefix
		$schema = str_replace ( '%TABLE_PREFIX%', $prefix, $schema );
		// Split by dollar signs - and cleanup
		if (! ($statements = array_filter ( array_map ( 'trim', 
				// Thanks, http://stackoverflow.com/a/3147901
				preg_split ( "/\\$(?=(?:[^']*'[^']*')*[^']*$)/", $schema ) ) )))
			return $this->abort ( 'Error parsing SQL schema', $debug );
		
		db_query ( 'SET SESSION SQL_MODE =""', false );
		foreach ( $statements as $k => $sql ) {
			if (db_query ( $sql, false ))
				continue;
			if (db_error () != null) {
				$error = "[$sql] " . db_error ();
				if ($abort)
					return $this->abort ( $error, $debug );
			}
		}
		
		return true;
	}
}

?>