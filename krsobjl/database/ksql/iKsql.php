<?php
interface iKsql
{
	public function connect();
	public function disconnect();
	public function createsql();
	public function request();
	public function fetch();
	public function numrow();
	public function begintransaction();
	public function commit();
	public function rollback();
	public function resetfield();
	public function numrows();
}
?>