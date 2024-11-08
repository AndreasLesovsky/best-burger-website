<?php
require("includes/common.inc.php");
require("includes/db.inc.php");
require("includes/config.inc.php");

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$conn = dbConnect();
$msg = "";

// Sicheres Löschen einer Reservierung
if (isset($_POST["btnDel"])) {
    $id = intval($_POST["btnDel"]);
    $stmt = $conn->prepare("DELETE FROM tbl_reservations WHERE ID = ?");
    $stmt->bind_param("i", $id);
    $ok = $stmt->execute();

    if ($ok) {
        $msg .= '<p class="success">Die Reservierung wurde erfolgreich gelöscht!</p>';
    } else {
        $msg .= '<p class="error">Leider konnte die Reservierung nicht gelöscht werden.</p>';
    }
    $stmt->close();
}

// Filterabfrage
$where = "";
$params = [];
$types = "";

if (isset($_POST["btnFilter"])) {
    $filters = [
        'name' => 'fltrName',
        'email' => 'fltrEmail',
        'phone' => 'fltrTel',
        'res_date' => 'fltrDate',
        'table_number' => 'fltrTable'
    ];

    $conditions = [];
    foreach ($filters as $column => $inputName) {
        if (!empty($_POST[$inputName])) {
            $conditions[] = "$column = ?";
            $params[] = $_POST[$inputName];
            $types .= "s";
        }
    }

    if (count($conditions) > 0) {
        $where = "WHERE " . implode(" AND ", $conditions);
    }
}

// Pagination
$anzDSeProSeite = 50;
$seite = isset($_GET["seite"]) ? intval($_GET["seite"]) : 1;

$stmt = $conn->prepare("SELECT COUNT(*) AS anzahl FROM tbl_reservations $where");
if (count($params) > 0) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$daten = $stmt->get_result();
$ds = $daten->fetch_object();
$anzDSe = $ds->anzahl;
$maxAnzSeiten = ceil($anzDSe / $anzDSeProSeite);
$stmt->close();

// Sortierung
$sortColumns = [
    'ID' => 'id',
    'Name' => 'name',
    'Email' => 'email',
    'Tel' => 'phone',
    'Date' => 'res_date',
    'Table' => 'table_number'
];

$order = "";
foreach ($sortColumns as $key => $column) {
    $sortOrderVar = "sortorder" . $key;
    $$sortOrderVar = isset($_POST["btnOrder$key"]) ? htmlspecialchars($_POST["btnOrder$key"]) : "";

    if (isset($_POST["btnOrder$key"])) {
        switch ($$sortOrderVar) {
            case "":
                $order = "ORDER BY $column ASC";
                $$sortOrderVar = "ASC";
                break;
            case "ASC":
                $order = "ORDER BY $column DESC";
                $$sortOrderVar = "DESC";
                break;
            case "DESC":
                $order = "";
                $$sortOrderVar = "";
                break;
        }
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
    <script src="script_view_reservations.js"></script>
</head>

<body>
    <header>
        <a href="index.php" class="logo">
            <h1>best burger</h1>
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
            <div class="inner-container">
                <h2>Reservierungen</h2>

                <?php echo $msg; ?>

                <form method="post">
                    <!-- Pagination -->
                    <div class="pagination">
                        <a href="?seite=<?php echo max(1, $seite - 1); ?>" class="btn-change-page">
                            <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="var(--col-font)">
                                <path d="M400-80 0-480l400-400 71 71-329 329 329 329-71 71Z" />
                            </svg>
                        </a>

                        Seite <input type="text" value="<?php echo htmlspecialchars($seite); ?>" name="seite" readonly> von <?php echo htmlspecialchars($maxAnzSeiten); ?>

                        <a href="?seite=<?php echo min($maxAnzSeiten, $seite + 1); ?>" class="btn-change-page">
                            <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="var(--col-font)">
                                <path d="m321-80-71-71 329-329-329-329 71-71 400 400L321-80Z" />
                            </svg>
                        </a>
                    </div>

                    <table>
                        <thead>
                            <tr>
                                <th scope="col">ID</th>
                                <th scope="col">Name</th>
                                <th scope="col">E-Mail</th>
                                <th scope="col">Telefon</th>
                                <th scope="col">Datum</th>
                                <th scope="col">Uhrzeit</th>
                                <th scope="col">Tisch-Nr.</th>
                                <th></th>
                            </tr>
                            <tr>
                                <!-- Filterformular -->
                                <td></td>
                                <td><input type="text" name="fltrName" placeholder="Name" value="<?php echo htmlspecialchars($_POST["fltrName"] ?? ''); ?>"></td>
                                <td><input type="email" name="fltrEmail" placeholder="E-Mail" value="<?php echo htmlspecialchars($_POST["fltrEmail"] ?? ''); ?>"></td>
                                <td><input type="tel" name="fltrTel" placeholder="Telefon" value="<?php echo htmlspecialchars($_POST["fltrTel"] ?? ''); ?>"></td>
                                <td><input type="date" name="fltrDate" placeholder="Datum" value="<?php echo htmlspecialchars($_POST["fltrDate"] ?? ''); ?>"></td>
                                <td></td>
                                <td><input type="number" name="fltrTable" placeholder="Tisch-Nr." value="<?php echo htmlspecialchars($_POST["fltrTable"] ?? ''); ?>"></td>
                                <td><button type="submit" name="btnFilter" title="Filtern">
                                        <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="var(--col-font">
                                            <path d="M784-120 532-372q-30 24-69 38t-83 14q-109 0-184.5-75.5T120-580q0-109 75.5-184.5T380-840q109 0 184.5 75.5T640-580q0 44-14 83t-38 69l252 252-56 56ZM380-400q75 0 127.5-52.5T560-580q0-75-52.5-127.5T380-760q-75 0-127.5 52.5T200-580q0 75 52.5 127.5T380-400Z" />
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <!-- Sortierbuttons -->
                                <td>
                                    <button type="submit" title="Nach ID Sortieren" name="btnOrderID" value="<?php echo htmlspecialchars($sortorderID); ?>">
                                        <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="var(--col-font)">
                                            <path d="M120-240v-80h240v80H120Zm0-200v-80h480v80H120Zm0-200v-80h720v80H120Z" />
                                        </svg>
                                    </button>
                                </td>
                                <td>
                                    <button type="submit" title="Nach Name Sortieren" name="btnOrderName" value="<?php echo htmlspecialchars($sortorderName); ?>">
                                        <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="var(--col-font)">
                                            <path d="M120-240v-80h240v80H120Zm0-200v-80h480v80H120Zm0-200v-80h720v80H120Z" />
                                        </svg>
                                    </button>
                                </td>
                                <td>
                                    <button type="submit" title="Nach Emailadresse Sortieren" name="btnOrderEmail" value="<?php echo htmlspecialchars($sortorderEmail); ?>">
                                        <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="var(--col-font)">
                                            <path d="M120-240v-80h240v80H120Zm0-200v-80h480v80H120Zm0-200v-80h720v80H120Z" />
                                        </svg>
                                    </button>
                                </td>
                                <td>
                                    <button type="submit" title="Nach Telefonnummer Sortieren" name="btnOrderTel" value="<?php echo htmlspecialchars($sortorderTel); ?>">
                                        <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="var(--col-font)">
                                            <path d="M120-240v-80h240v80H120Zm0-200v-80h480v80H120Zm0-200v-80h720v80H120Z" />
                                        </svg>
                                    </button>
                                </td>

                                <td>
                                    <button type="submit" title="Nach Datum Sortieren" name="btnOrderDate" value="<?php echo htmlspecialchars($sortorderDate); ?>">
                                        <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="var(--col-font)">
                                            <path d="M120-240v-80h240v80H120Zm0-200v-80h480v80H120Zm0-200v-80h720v80H120Z" />
                                        </svg>
                                    </button>
                                </td>

                                <td></td>

                                <td>

                                    <button type="submit" title="Nach Tischnummer Sortieren" name="btnOrderTable" value="<?php echo htmlspecialchars($sortorderTable); ?>">
                                        <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="var(--col-font)">
                                            <path d="M120-240v-80h240v80H120Zm0-200v-80h480v80H120Zm0-200v-80h720v80H120Z" />
                                        </svg>
                                    </button>
                                </td>

                                <td></td>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Berechnung des Offset für die aktuelle Seite
                            $offset = ($seite - 1) * $anzDSeProSeite;

                            // Bereite das SQL-Statement vor (inkl. Filter und Sortierung)
                            $stmt = $conn->prepare("SELECT * FROM tbl_reservations " . $where . " " . $order . " LIMIT ?, ?");

                            // Füge die Paginierungsparameter (offset und Anzahl pro Seite) zu den Parametern hinzu
                            $params[] = $offset;
                            $params[] = $anzDSeProSeite;
                            $types .= "ii";

                            // Binde die Parameter (Filter + Paginierung)
                            if (count($params) > 0) {
                                $stmt->bind_param($types, ...$params);
                            }

                            // Führe das Statement aus
                            $stmt->execute();
                            $result = $stmt->get_result();

                            // Ausgabe der Ergebnisse
                            while ($row = $result->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($row["id"]) . "</td>";
                                echo "<td>" . htmlspecialchars($row["name"]) . "</td>";
                                echo "<td>" . htmlspecialchars($row["email"]) . "</td>";
                                echo "<td>" . htmlspecialchars($row["phone"]) . "</td>";
                                echo "<td>" . htmlspecialchars($row["res_date"]) . "</td>";
                                echo "<td>" . htmlspecialchars($row["res_time"]) . "</td>";
                                echo "<td>" . htmlspecialchars($row["table_number"]) . "</td>";
                                echo '<td>
                                        <button type="submit" name="btnDel" value="' . htmlspecialchars($row["id"]) . '" onclick="return confirm(\'Möchten Sie diese Reservierung wirklich löschen?\');" title="Reservierung Löschen">
                                            <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="var(--col-font)">
                                                <path d="m376-300 104-104 104 104 56-56-104-104 104-104-56-56-104 104-104-104-56 56 104 104-104 104 56 56Zm-96 180q-33 0-56.5-23.5T200-200v-520h-40v-80h200v-40h240v40h200v80h-40v520q0 33-23.5 56.5T680-120H280Zm400-600H280v520h400v-520Zm-400 0v520-520Z"/>
                                            </svg>
                                        </button>
                                    </td>';
                                echo "</tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </form>
            </div>
        </section>
    </main>

    <footer id="footer">
        <div class="inner-container">
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
                ©2024 best burger
            </address>
        </div>
    </footer>
</body>

</html>