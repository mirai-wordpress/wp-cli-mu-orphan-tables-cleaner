<?php

if ( defined( 'WP_CLI' ) && WP_CLI ) {

	/**
	 * A robust random post generator built for developers.
	 */
	class Orphan_Tables extends WP_CLI_Command {

		protected $db;

		public function __construct() {
			$this->db = $GLOBALS['wpdb'];
		}

		/**
		 * List or drop orphan tables from Mirai Multisite WP
		 *
		 * ## Usage
		 * $ wp orphan tables list -> lists orphan tables
		 * $ wp orphan tables list_drops -> lists executable drop commands
		 * $ wp orphan tables drop -> drops orphan tables
		 */

		private function get_orphan_tables(){

			if ( ! is_multisite() ) {
				WP_CLI::error( 'This is not a multisite installation. This command is for multisite only.' );
			}

			$dbname = $this->db->dbname;

			//get existing blogs IDs
			$blog_ids = $this->db->get_col( "SELECT blog_id FROM ".$this->db->blogs );
			
			$max_blog_id = max($blog_ids);
			
			$orphan_tablenames = [];

			//search tables with name prefix containing non-existing blog id's
			for($i = 1 ; $i < $max_blog_id ; $i++){
					
				if( ! in_array($i, $blog_ids)){
					
					$tables_like = $dbname."\_".$i."\_";
					$blog_tables = $this->db->get_col("SELECT table_name FROM information_schema.tables where table_schema='$dbname' AND table_name LIKE '$tables_like%'");
					
					foreach($blog_tables as $table)
						$orphan_tablenames[] = $table;
				}
			}
			if(count($orphan_tablenames)==0)
			{
				echo "No orphan tables found!\n";
				exit;
			}

			return $orphan_tablenames;
		}		

		//prints orphan table names in plain text
		public function list_tables() {

			$orphan_tablenames = $this->get_orphan_tables();
			foreach($orphan_tablenames as $table) 
				echo $table."\n";
		}

		//prints drop statements for orphan tables
		public function list_drops() {

			$orphan_tablenames = $this->get_orphan_tables();
			foreach($orphan_tablenames as $table)
				echo "DROP TABLE ".$table.";\n";
		}

		//drops orphan tables
		public function drop_tables() {

			WP_CLI::confirm( 'BE CAREFUL, a drop statement cannot be undone so please backup your database before proceeding. Are you sure you want to proceed?', $assoc_args = array() );

			$orphan_tablenames = $this->get_orphan_tables();
			foreach($orphan_tablenames as $table){
				if($this->db->query( "DROP TABLE IF EXISTS $table" ))
					echo "Succesfully dropped table ".$table."\n";
				else
					echo "Could not drop table ".$table."\n";
			}
		}
	}

	WP_CLI::add_command( 'orphan_tables', 'Orphan_Tables' );
}
