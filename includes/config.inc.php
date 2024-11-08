<?php
define("TESTMODUS",false); //Konstante; gibt an, ob wir uns in einem Development-System (TESTMODUS ist true) oder in einem Produktivsystem (TESTMODUS ist false) befinden

define("DB",[
	"host" => "sql136.your-server.de",
	"user" => "andreac_211",
	"pwd" => "uQFUwK4a5aNKfF3B",
	// "host" => "localhost",
	// "user" => "root",
	// "pwd" => "",
	"name" => "db_reservations",
	"charset" => "utf8mb4",
	"errorpages" => [
		"dbconnect" => "errors/dbconnect.html"
	]
]);

if(TESTMODUS) {
	error_reporting(E_ALL);
	ini_set("display_errors",1);
}
else {
	error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
	ini_set("display_errors",0);
}
?>