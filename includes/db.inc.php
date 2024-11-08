<?php
function dbConnect():mysqli {
	try {
		// Schritt 1: Verbindung mit dem Datenbankserver herstellen
		$conn = new MySQLi(DB["host"],DB["user"],DB["pwd"],DB["name"]); //VERSUCH, eine Verbindung mit dem DB-Server herzustellen
		if($conn->connect_errno>0) {
			//gilt für PHP bis v8.1: es ist irgendein Fehler während des Verbindungsaufbaus (bzw. der Auswahl der Datenbank) aufgetreten --> Abbruch!
			if(TESTMODUS) {
				die("Fehler im Verbindungsaufbau: " . $conn->connect_error);
			}
			else {
				header("Location: " . DB["errorpages"]["dbconnect"]);
			}
		}
		$conn->set_charset(DB["charset"]);
	}
	catch(Exception $e) {
		if(TESTMODUS) {
			ta($e);
			die("Fehler im Verbindungsaufbau");
		}
		else {
			header("Location: " . DB["errorpages"]["dbconnect"]);
		}
	}
	
	return $conn;
}

function dbQuery(mysqli $conn, string $sql):mysqli_result|bool {
	try {
		$antwort = $conn->query($sql);
		if($antwort===false) {
			//das Statement war fehlerhaft --> Abbruch
			if(TESTMODUS) {
				die("Fehler im SQL-Statement: " . $conn->error ."<br>" . $sql);
			}
			else {
				header("Location: errors/dbquery.html");
			}
		}
	}
	catch(Exception $e) {
		if(TESTMODUS) {
			ta($e);
			die("Fehler im SQL-Statement:<br>" . $sql);
		}
		else {
			header("Location: errors/dbquery.html");
		}
	}
	
	return $antwort;
}
?>