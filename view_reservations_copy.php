<?php
require("includes/common.inc.php");
require("includes/db.inc.php");
require("includes/config.inc.php");

$conn = dbConnect();
$msg = "";


if (count($_POST) > 0) {
	if (isset($_POST["btnDel"])) {
		$sql = "
			DELETE FROM tbl_reservations
			WHERE(
				ID=" . $_POST["btnDel"] . "
			)
		";
		//ta($sql);
		$ok = dbQuery($conn, $sql);

		if ($ok) {
			$msg .= '<p class="success">Die Reservierung wurde erfolgreich gelöscht!</p>';
		} else {
			$msg .= '<p class="error">Leider konnte die Reservierung nicht gelöscht werden.</p>';
		}
	}
}

$where = "";
$order = "";
$sortorderDate = "";

if (count($_POST) > 0 && isset($_POST["btnFilter"])) {
	$arr = [];
	if (isset($_POST["fltrName"]) && strlen($_POST["fltrName"]) > 0) {
		$arr[] = "name='" . $_POST["fltrName"] . "'";
	}
	if (isset($_POST["fltrEmail"]) && strlen($_POST["fltrEmail"]) > 0) {
		$arr[] = "email='" . $_POST["fltrEmail"] . "'";
	}
	if (isset($_POST["fltrTel"]) && strlen($_POST["fltrTel"]) > 0) {
		$arr[] = "phone='" . $_POST["fltrTel"] . "'";
	}
	if (isset($_POST["fltrDate"]) && strlen($_POST["fltrDate"]) > 0) {
		$arr[] = "res_date='" . $_POST["fltrDate"] . "'";
	}
	if (isset($_POST["fltrTable"]) && strlen($_POST["fltrTable"]) > 0) {
		$arr[] = "table_number='" . $_POST["fltrTable"] . "'";
	}

	if (count($arr) > 0) {
		$where = "
			WHERE(
				" . implode(" AND ", $arr) . "
			)
		";
	}
}

$anzDSeProSeite = 50; //Anzahl der Datensätze pro Seite
$seite = 1; //aktuelle Seite

// ---- ermittle die Anzahl der Datensätze in der Tabelle tbl_user: ----
$sql = "
	SELECT
		COUNT(*) AS anzahl
	FROM tbl_reservations
	" . $where . "
";
$daten = dbQuery($conn, $sql);
$ds = $daten->fetch_object();
$anzDSe = $ds->anzahl; //Anzahl der Datensätze gesamt in der Tabelle tbl_user
$maxAnzSeiten = ceil($anzDSe / $anzDSeProSeite); //Anzahl der Seiten, die man durchblättern kann
// ---------------------------------------------------------------------

if (count($_POST) > 0) {
	$seite = intval($_POST["seite"]); //ermittelt die Seite, auf der sich der User tatsächlich befindet
	$sortorderDate = $_POST["sortorderDate"]; //bestimmt die Sortierreihenfolge für den Nachnamen aus den zuvor vom User bestimmten Sortierkriterien
	if ($sortorderDate != "") {
		$order = "ORDER BY res_date " . $sortorderDate;
	}

	if (isset($_POST["btnChangePage"])) {
		$change = intval($_POST["btnChangePage"]); //Hilfsvariable; gibt an, ob weiter- (1) oder zurück-geblättert (-1) werden soll
		if ($seite + $change > 0 && $seite + $change <= $maxAnzSeiten) {
			$seite = $seite + $change;
		}
	}

	// ---- sortieren: ----
	if (isset($_POST["btnOrderDate"])) {
		//es wurde der Button zum Sortieren des Nachnamens geklickt
		switch ($sortorderNN) {
			case "":
				//aktuell sind die Daten unsortiert --> nun aufsteigend sortieren
				$order = "ORDER BY res_date ASC";
				$sortorderNN = "ASC";
				break;

			case "ASC":
				//aktuells sind die Daten aufsteigend sortiert --> nun absteigend sortieren
				$order = "ORDER BY res_date DESC";
				$sortorderNN = "DESC";
				break;

			case "DESC":
				//aktuell sind die Daten absteigend sortiert  --> nun wieder nicht mehr sortieren
				$order = "";
				$sortorderNN = "";
				break;
		}
	}
	// --------------------
}
?>
<html lang="de">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, height=device-height, initial-scale=1.0, user-scalable=yes">
	<title>best burger</title>
	<link rel="stylesheet" href="css/importer.css">
	<link rel="stylesheet" href="css/reset.css">
	<link rel="stylesheet" href="css/config.css">
	<link rel="stylesheet" href="css/style.css">
	<link rel="stylesheet" href="css/common.css">
	<link rel="stylesheet" href="css/media.css">
	<link rel="stylesheet" href="css/view_reservations.css">
	<link rel="icon" href="media/icon.svg">
	<script src="https://kit.fontawesome.com/7933e77e42.js" crossorigin="anonymous"></script>
	<script defer src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"
		integrity="sha512-7eHRwcbYkK4d9g/6tD/mhkf++eoTHwpNM9woBxtPUBWm67zeAfFC+HrdoE2GanKeocly/VxeLvIqwvCdk7qScg=="
		crossorigin="anonymous" referrerpolicy="no-referrer"></script>
	<script src="script.js"></script>
</head>

<body>
	<header>
		<a href="index.php" class="logo">
			<h1>
				best burger
			</h1>
			<small>Premium Burger Restaurant</small>
		</a>

		<nav>
			<ul>
				<li><a href="index.php#ueber-uns">Über Uns</a></li>
				<li><a href="index.php#burgers">Burgers</a></li>
				<li><a href="index.php#wings">Wings</a></li>
				<li><a href="index.php#reserve">Reservieren</a></li>
			</ul>
		</nav>

		<ul class="social">
			<li><a href="#" class="hvr-grow-rotate"><i class="fa-brands fa-facebook-f"></i></a></li>
			<li><a href="#" class="hvr-grow-rotate"><i class="fa-brands fa-x-twitter"></i></a></li>
			<li><a href="#" class="hvr-grow-rotate"><i class="fa-brands fa-instagram"></i></a></li>
		</ul>

		<button id="menu-btn" aria-expanded="false">
			<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px">
				<path d="M120-240v-80h720v80H120Zm0-200v-80h720v80H120Zm0-200v-80h720v80H120Z" />
			</svg>
		</button>
	</header>

	<main>
		<section id="reservations">
			<div class="wrapper-inner">
				<h2>Reservierungen</h2>

				<form method="post">

					<input type="hidden" name="sortorderDate" value="<?php echo ($sortorderDate); ?>">
					<div class="pagination">
						<button type="submit" name="btnChangePage" value="-1" class="btn-change-page">
							<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="var(--col-font)">
								<path d="M400-80 0-480l400-400 71 71-329 329 329 329-71 71Z" />
							</svg>
						</button>
						Seite <input type="text" value="<?php echo ($seite); ?>" name="seite" readonly> von <?php echo ($maxAnzSeiten); ?>
						<button type="submit" name="btnChangePage" value="1" class="btn-change-page">
							<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="var(--col-font)">
								<path d="m321-80-71-71 329-329-329-329 71-71 400 400L321-80Z" />
							</svg>
						</button>
					</div>

					<table>
						<thead>
							<tr>
								<th scope="col">ID</th>
								<th scope="col">Name</th>
								<th scope="col">Emailadresse</th>
								<th scope="col">Telefonnummer</th>
								<th scope="col">
									Reservierungsdatum
									<button type="submit" title="Nach Datum Sortieren" name="btnOrderDate" value="<?php echo ($sortorderDate); ?>">
										<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="var(--col-font)">
											<path d="M120-240v-80h240v80H120Zm0-200v-80h480v80H120Zm0-200v-80h720v80H120Z" />
										</svg>
									</button>
								</th>
								<th scope="col">Reservierungsuhrzeit</th>
								<th scope="col">Tischnummer</th>
								<td></td>
							</tr>
							<tr>
								<td></td>
								<td><input type="text" name="fltrName" placeholder="Nach Name suchen" value="<?php echo htmlentities($_POST['fltrName'] ?? ''); ?>"></td>

								<td><input type="email" name="fltrEmail" placeholder="Nach Emailadresse suchen" value="<?php echo htmlentities($_POST['fltrEmail'] ?? ''); ?>"></td>
								<td><input type="tel" name="fltrTel" placeholder="Nach Telefonnr. suchen" value="<?php echo htmlentities($_POST['fltrTel'] ?? ''); ?>"></td>
								<td><input type="date" name="fltrDate" value="<?php echo htmlentities($_POST['fltrDate'] ?? ''); ?>"></td>
								<td></td>
								<td><input type="number" name="fltrTable" placeholder="Nach Tischnr. suchen" value="<?php echo htmlentities($_POST['fltrTable'] ?? ''); ?>"></td>
								<td>
									<button type="submit" name="btnFilter" title="Nach Einträgen suchen">
										<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="var(--col-font">
											<path d="M784-120 532-372q-30 24-69 38t-83 14q-109 0-184.5-75.5T120-580q0-109 75.5-184.5T380-840q109 0 184.5 75.5T640-580q0 44-14 83t-38 69l252 252-56 56ZM380-400q75 0 127.5-52.5T560-580q0-75-52.5-127.5T380-760q-75 0-127.5 52.5T200-580q0 75 52.5 127.5T380-400Z" />
										</svg>
									</button>
								</td>
							</tr>
						</thead>
						<tbody>
							<?php
							$sql = "
								SELECT
									*
								FROM tbl_reservations
								" . $where . "
								" . $order . "
								LIMIT " . ($seite - 1) * $anzDSeProSeite . "," . $anzDSeProSeite . "
							";
							$result = $conn->query($sql);
							if ($result->num_rows > 0) {
								while ($row = $result->fetch_object()) {
									echo ('
								<tr>
									<td>' . $row->id . '</td>
									<td>' . $row->name . '</td>
									<td>' . $row->email . '</td>
									<td>' . $row->phone . '</td>
									<td>' . $row->res_date . '</td>
									<td>' . $row->res_time . '</td>
									<td>' . $row->table_number . '</td>
									<td>
										<button type="submit" name="btnDel" title="Eintrag Löschen" value="' . $row->id . '">
											<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="var(--col-font)">
												<path d="m376-300 104-104 104 104 56-56-104-104 104-104-56-56-104 104-104-104-56 56 104 104-104 104 56 56Zm-96 180q-33 0-56.5-23.5T200-200v-520h-40v-80h200v-40h240v40h200v80h-40v520q0 33-23.5 56.5T680-120H280Zm400-600H280v520h400v-520Zm-400 0v520-520Z"/>
											</svg>
										</button>
									</td>
								</tr>');
								}
							} else {
								echo "<p class='error'>Keine Reservierungen gefunden.</p>";
							}
							?>
						</tbody>
					</table>
				</form>
			</div>
		</section>
	</main>

	<footer id="footer">
		<div class="wrapper-inner">
			<ul>
				<li><a href="#">Catering</a></li>
				<li><a href="#">Jobs</a></li>
			</ul>
			<ul class="social">
				<li><a href="#" class="hvr-grow-rotate"><i class="fa-brands fa-facebook-f"></i></a></li>
				<li><a href="#" class="hvr-grow-rotate"><i class="fa-brands fa-x-twitter"></i></a></li>
				<li><a href="#" class="hvr-grow-rotate"><i class="fa-brands fa-instagram"></i></a></li>
			</ul>
			<address>
				©2024 XY
			</address>
		</div>
	</footer>
</body>

</html>