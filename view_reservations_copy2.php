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
            WHERE ID=" . intval($_POST["btnDel"]);
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
		$arr[] = "name='" . mysqli_real_escape_string($conn, $_POST["fltrName"]) . "'";
	}
	if (isset($_POST["fltrEmail"]) && strlen($_POST["fltrEmail"]) > 0) {
		$arr[] = "email='" . mysqli_real_escape_string($conn, $_POST["fltrEmail"]) . "'";
	}
	if (isset($_POST["fltrTel"]) && strlen($_POST["fltrTel"]) > 0) {
		$arr[] = "phone='" . mysqli_real_escape_string($conn, $_POST["fltrTel"]) . "'";
	}
	if (isset($_POST["fltrDate"]) && strlen($_POST["fltrDate"]) > 0) {
		$arr[] = "res_date='" . mysqli_real_escape_string($conn, $_POST["fltrDate"]) . "'";
	}
	if (isset($_POST["fltrTable"]) && strlen($_POST["fltrTable"]) > 0) {
		$arr[] = "table_number='" . mysqli_real_escape_string($conn, $_POST["fltrTable"]) . "'";
	}

	if (count($arr) > 0) {
		$where = "WHERE " . implode(" AND ", $arr);
	}
}

$anzDSeProSeite = 50; // Anzahl der Datensätze pro Seite
$seite = isset($_GET["seite"]) ? intval($_GET["seite"]) : 1; // aktuelle Seite

// ---- ermittle die Anzahl der Datensätze in der Tabelle tbl_user: ----
$sql = "
    SELECT COUNT(*) AS anzahl
    FROM tbl_reservations
    " . $where;
$daten = dbQuery($conn, $sql);
$ds = $daten->fetch_object();
$anzDSe = $ds->anzahl; // Anzahl der Datensätze gesamt in der Tabelle tbl_user
$maxAnzSeiten = ceil($anzDSe / $anzDSeProSeite); // Anzahl der Seiten, die man durchblättern kann
// ---------------------------------------------------------------------

$order = "";
if (count($_POST) > 0 && isset($_POST["btnOrderDate"])) {
	// es wurde der Button zum Sortieren des Datums geklickt
	switch ($sortorderDate) {
		case "":
			// aktuell sind die Daten unsortiert --> nun aufsteigend sortieren
			$order = "ORDER BY res_date ASC";
			$sortorderDate = "ASC";
			break;

		case "ASC":
			// aktuell sind die Daten aufsteigend sortiert --> nun absteigend sortieren
			$order = "ORDER BY res_date DESC";
			$sortorderDate = "DESC";
			break;

		case "DESC":
			// aktuell sind die Daten absteigend sortiert --> nun wieder nicht mehr sortieren
			$order = "";
			$sortorderDate = "";
			break;
	}
}
?>
<html lang="de">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, height=device-height, initial-scale=1.0, user-scalable=yes">
	<title>Best Burger</title>
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
				Best Burger
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
			<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="http://www.w3.org/2000/svg" width="24px">
				<path d="M120-240v-80h720v80H120Zm0-200v-80h720v80H120Zm0-200v-80h720v80H120Z" />
			</svg>
		</button>
	</header>

	<main>
		<section id="reservations">
			<div class="wrapper-inner">
				<h2>Reservierungen</h2>

				<?php echo $msg; ?>




				<button type="submit" title="Nach Datum Sortieren" name="btnOrderDate" value="<?php echo ($sortorderDate); ?>">
					<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="var(--col-font)">
						<path d="M120-240v-80h240v80H120Zm0-200v-80h480v80H120Zm0-200v-80h720v80H120Z" />
					</svg>
				</button>

				<!-- Filterformular -->
				<input type="text" name="fltrName" placeholder="Name" value="<?php echo htmlspecialchars($_POST["fltrName"] ?? ""); ?>">
				<input type="text" name="fltrEmail" placeholder="Email" value="<?php echo htmlspecialchars($_POST["fltrEmail"] ?? ""); ?>">
				<input type="text" name="fltrTel" placeholder="Telefon" value="<?php echo htmlspecialchars($_POST["fltrTel"] ?? ""); ?>">
				<input type="text" name="fltrDate" placeholder="Datum" value="<?php echo htmlspecialchars($_POST["fltrDate"] ?? ""); ?>">
				<input type="text" name="fltrTable" placeholder="Tischnummer" value="<?php echo htmlspecialchars($_POST["fltrTable"] ?? ""); ?>">
				<button type="submit" name="btnFilter" title="Nach Einträgen suchen">
					<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="var(--col-font">
						<path d="M784-120 532-372q-30 24-69 38t-83 14q-109 0-184.5-75.5T120-580q0-109 75.5-184.5T380-840q109 0 184.5 75.5T640-580q0 44-14 83t-38 69l252 252-56 56ZM380-400q75 0 127.5-52.5T560-580q0-75-52.5-127.5T380-760q-75 0-127.5 52.5T200-580q0 75 52.5 127.5T380-400Z" />
					</svg>
				</button>




				<form method="post">
					<input type="hidden" name="sortorderDate" value="<?php echo htmlspecialchars($sortorderDate); ?>">

					<a href="?seite=<?php echo max(1, $seite - 1); ?>" class="btn-change-page">
						<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="var(--col-font)">
							<path d="M400-80 0-480l400-400 71 71-329 329 329 329-71 71Z" />
						</svg>
					</a>

					Seite <input type="text" value="<?php echo htmlspecialchars($seite); ?>" name="seite" readonly> von <?php echo htmlspecialchars($maxAnzSeiten); ?>

					<a href="?seite=<?php echo min($maxAnzSeiten, $seite + 1); ?>" class="btn-change-page">
						<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="var(--col-font)">
							<path d="M0-80 400-480l71 71-329 329 329 329-71 71Z" />
						</svg>
					</a>
					<table>
						<thead>
							<tr>
								<th scope="col">ID</th>
								<th scope="col">Name</th>
								<th scope="col">Email</th>
								<th scope="col">Telefon</th>
								<th scope="col">Datum</th>
								<th scope="col">Tischnummer</th>
								<th scope="col">Aktionen</th>
							</tr>
						</thead>
						<tbody>
							<?php
							$sql = "
                            SELECT *
                            FROM tbl_reservations
                            " . $where . "
                            " . $order . "
                            LIMIT " . ($seite - 1) * $anzDSeProSeite . ", " . $anzDSeProSeite . "
                        ";
							$daten = dbQuery($conn, $sql);
							if ($daten->num_rows > 0) {
							while ($ds = $daten->fetch_object()) {
							?>
								<tr>
									<td><?php echo htmlspecialchars($ds->id); ?></td>
									<td><?php echo htmlspecialchars($ds->name); ?></td>
									<td><?php echo htmlspecialchars($ds->email); ?></td>
									<td><?php echo htmlspecialchars($ds->phone); ?></td>
									<td><?php echo htmlspecialchars($ds->res_date); ?></td>
									<td><?php echo htmlspecialchars($ds->table_number); ?></td>
									<td>
										<form method="post" style="display:inline;">
											<input type="hidden" name="btnDel" value="<?php echo $ds->id; ?>">
											<button type="submit">
												<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="var(--col-font)">
													<path d="m376-300 104-104 104 104 56-56-104-104 104-104-56-56-104 104-104-104-56 56 104 104-104 104 56 56Zm-96 180q-33 0-56.5-23.5T200-200v-520h-40v-80h200v-40h240v40h200v80h-40v520q0 33-23.5 56.5T680-120H280Zm400-600H280v520h400v-520Zm-400 0v520-520Z" />
												</svg>
											</button>
										</form>
									</td>
								</tr>
							<?php
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

	<footer>
		<p>&copy; 2024 Best Burger. Alle Rechte vorbehalten.</p>
	</footer>

</body>

</html>