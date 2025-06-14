<?php
	class DBConn {
		private $db_host = "localhost:3306";
		private $db_user = "astole";
		private $db_pass = "fsTqAcxpppaYE9q4fsP4vLXs!_wigN8uA2Ndovd7";
		private $db_tbl = "astole";

		public $conn = null;
		public $sql = null;
		public $result = null;
		public $row = null;

		public function __construct() {
			$this->conn = new mysqli($this->db_host, $this->db_user, $this->db_pass, $this->db_tbl);
		}
	}
?>
