<?php
require("includes/db.inc.php");
require("includes/config.inc.php");
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$conn = dbConnect();
$msg = '';

if (count($_POST) > 0) {
	// Überprüfen des reCAPTCHA
	$recaptchaSecret = '';
	$recaptchaResponse = $_POST['g-recaptcha-response'];

	$response = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=$recaptchaSecret&response=$recaptchaResponse");
	$responseKeys = json_decode($response, true);

	if (intval($responseKeys["success"]) !== 1) {
		$msg .= "<p class='error'>Bitte bestätigen Sie, dass Sie kein Roboter sind.</p>";
	} else {
		// Eingaben sichern
		$name = htmlspecialchars(trim($_POST['name']));
		$email = htmlspecialchars(trim($_POST['email']));
		$phone = htmlspecialchars(trim($_POST['phone']));
		$resDate = htmlspecialchars(trim($_POST['resTag']));
		$resHour = htmlspecialchars(trim($_POST['resHour']));
		$resMinute = htmlspecialchars(trim($_POST['resMinute']));
		$table = htmlspecialchars(trim($_POST['selectedTable']));

		// zusätzliche serverseitige Validierung für Stunden und Minuten
		if (($resHour >= 11 && $resHour <= 23) && ($resMinute == '00' || $resMinute == '30')) {
			$resTime = $resHour . ':' . $resMinute . ':00';

			// Überprüfen, ob die E-Mail-Adresse in der letzten Woche bereits reserviert hat
			$sql_check = "SELECT COUNT(*) AS count 
                        FROM tbl_reservations 
                        WHERE email = ? 
                        AND res_date >= DATE_SUB(CURDATE(), INTERVAL 1 WEEK)";
			$stmt_check = $conn->prepare($sql_check);
			$stmt_check->bind_param("s", $email);
			$stmt_check->execute();
			$result_check = $stmt_check->get_result();
			$row_check = $result_check->fetch_assoc();

			if ($row_check['count'] > 0) {
				$msg .= "<p class='error'>Sie haben bereits eine Reservierung in der letzten Woche vorgenommen.</p>";
			} else {
				// Daten in die Datenbank einfügen
				$sql = "INSERT INTO tbl_reservations 
                        (name, email, phone, res_date, res_time, table_number) 
                        VALUES (?, ?, ?, ?, ?, ?)";
				$stmt = $conn->prepare($sql);
				$stmt->bind_param("sssssi", $name, $email, $phone, $resDate, $resTime, $table);

				if ($stmt->execute()) {
					$msg .= "<p class='success'>Reservierung erfolgreich!</p>";

					// Bestätigungsmail versenden
					$mail = new PHPMailer();

					try {
						$mail->isSMTP();
						$mail->Host = ''; // SMTP-Server
						$mail->SMTPAuth = true;
						$mail->Username = ''; // SMTP-Benutzername
						$mail->Password = ''; // SMTP-Passwort
						$mail->SMTPSecure = 'tls'; // oder 'ssl'
						$mail->Port = 587; // oder 465 für SSL
						$mail->CharSet = 'UTF-8';

						$mail->setFrom('', 'Best Burger');
						$mail->addAddress($email);

						$mail->isHTML(true);
						$mail->Subject = 'Bestätigung Ihrer Reservierung';
						$mail->Body = "Hallo $name,<br><br>Ihre Reservierung für den $resDate um $resTime Uhr an Tisch $table wurde erfolgreich bestätigt.<br><br>Vielen Dank!";

						$mail->send();
					} catch (Exception $e) {
						$msg .= "Fehler beim Versenden der Bestätigungsmail: " . $mail->ErrorInfo;
					}
				} else {
					$msg .= "Fehler: " . $stmt->error;
				}
			}
		} else {
			$msg .= "<p class='error'>Bitte wählen Sie eine Uhrzeit zwischen 11:00 und 23:30 Uhr.</p>";
		}
	}
}

$conn->close();
?>
<!DOCTYPE html>
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
	<link rel="stylesheet" href="css/hover.css">
	<link rel="icon" href="media/icon.svg">
	<script src="https://kit.fontawesome.com/7933e77e42.js" crossorigin="anonymous"></script>
	<script defer src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"
		integrity="sha512-7eHRwcbYkK4d9g/6tD/mhkf++eoTHwpNM9woBxtPUBWm67zeAfFC+HrdoE2GanKeocly/VxeLvIqwvCdk7qScg=="
		crossorigin="anonymous" referrerpolicy="no-referrer"></script>
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
	<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
	<script src="https://www.google.com/recaptcha/api.js" async defer></script>
	<script src="script.js"></script>
	<script>
		document.addEventListener("DOMContentLoaded", function() {
			<?php if ($msg): ?>
				// Scrollen zur Kommentar-Sektion
				const reserveSection = document.getElementById("reserve");
				if (reserveSection) {
					reserveSection.scrollIntoView({
						behavior: 'smooth',
						block: 'center'
					});
				}
			<?php endif; ?>
		});
	</script>
	<script>
		document.addEventListener('keydown', function(event) {
			// Prüfen, ob Strg (oder Cmd) + Alt + A gedrückt wird
			if ((event.ctrlKey || event.metaKey) && event.altKey && event.key === 'a') {
				// Hier die URL zum Admin-Bereich einfügen
				window.location.href = 'view_reservations.php';
			}
		});
	</script>
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
				<li><a href="#ueber-uns">Über Uns</a></li>
				<li><a href="#burgers">Burgers</a></li>
				<li><a href="#wings">Wings</a></li>
				<li><a href="#reserve">Reservieren</a></li>
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
		<section>
			<div class="inner-container">
				<div class="content">
					<h2>Die besten Burger vom Grill!</h2>
					<p>
						UNSERE SIGNATURE PATTIES SIND 100% NATÜRLICH UND AUS FEINSTEM BEEF. Perfekt gewürzt und frisch zubereitet. Probieren Sie einen unserer All-Star-Burger oder stellen Sie sich Ihren eigenen Burger mit Ihren Lieblingszutaten zusammen.
					</p>
					<a href="#reserve" class="btn-primary">Jetzt Reservieren!</a>
				</div>
				<div class="placeholder"></div>
			</div>
		</section>

		<div class="sticky-header-bar"></div>

		<section id="all-stars">
			<div class="inner-container">
				<h2>The All-Stars</h2>
				<div>
					<div class="burger-card">
						<img src="media/feature_greatone.png" alt="greatone">
						<h4>The Great One</h4>
						<p>Zwei unserer legendären Beef-Patties, doppelt Cheddar, doppelt Bacon, karamellisierte Zwiebeln, ein Spiegelei und hausgemachte Mayo, serviert in einem Dinkel-Sesam Bun... es gibt einen Grund, warum wir ihn "The Great One" nennen!
						</p>
					</div>
					<div class="burger-card">
						<img src="media/feature_cheeseburger.png" alt="cheeseburger">
						<h4>Heavy Hitter Cheeseburger</h4>
						<p>Ein extra großes Beef-Patty, beladen mit amerikanischem, Schweizer, Cheddar und Mozzarella-Käse, Gewürzgurken, grünem Relish, Mayonnaise und Senf auf einem Brioche-Brötchen.
						</p>
					</div>
					<div class="burger-card">
						<img src="media/feature_inferno.png" alt="inferno">
						<h4>Mushroom “Swiss Miss” Burger</h4>
						<p>Unser legendäres Beef-Patty, belegt mit hausgemachter sautierter Champignon-Soße, Emmentaler, knusprigen Zwiebelringen und Mayonnaise auf einem Zwiebelbrötchen.
						</p>
					</div>
				</div>
				<a href="#burgers" class="btn-primary">Alle Burger</a>
			</div>
		</section>

		<section id="ueber-uns">
			<div class="inner-container">
				<h2>Über Uns</h2>
				<div>
					<p>
						Willkommen bei best burger, dem Premium Burger Restaurant. Wir bieten Ihnen einzigartige Burger-Kreationen aus erstklassigem Beef, die auf unseren speziellen Flammengrillern zubereitet werden. Diese verleihen unseren Burgern einen unverwechselbaren, köstlichen Geschmack.<br>
						<br>
						Unser Anspruch ist es, Ihnen höchste Qualität und besten Service zu bieten. Genießen Sie bei uns saftige Burger in einer einladenden und stilvollen Atmosphäre. Lassen Sie sich von unserer Leidenschaft für Burger und unserer Hingabe zur Perfektion begeistern.<br>
						<br>
						Besuchen Sie best burger und erleben Sie Burgergenuss auf höchstem Niveau!
					</p>

					<picture>
						<source srcset="media/1280x0/patrick-hodskins-DAiTQcL705E-unsplash.jpg" media="(max-width:36rem)" />
						<source srcset="media/1920x0/patrick-hodskins-DAiTQcL705E-unsplash.jpg" media="(max-width:64rem)" />
						<source srcset="media/2560x0/patrick-hodskins-DAiTQcL705E-unsplash.jpg" media="(max-width:80rem)" />
						<img src="media/patrick-hodskins-DAiTQcL705E-unsplash.jpg" alt="Grillofen" />
					</picture>
				</div>
			</div>
		</section>

		<section id="burgers">
			<div class="inner-container">
				<h2>Burgers</h2>
				<div>
					<div class="burger-item">
						<h4>Smokehouse BBQ Burger</h4>
						<p>Unser legendäres Beef-Patty, überzogen mit rauchiger BBQ-Sauce, serviert mit zerbröckeltem Blauschimmelkäse, Apfel-Whisky-Bacon-Marmelade, knusprigen Zwiebelringen, Bacon und Salat auf einem Brezelbrötchen. Dieser Burger ist uns richtig gut gelungen!</p>
					</div>
					<div class="burger-item">
						<h4>Beyond Meat Burger</h4>
						<p>Der weltweit erste pflanzenbasierte Burger, der aussieht, schmeckt und zufriedenstellt wie ein Rindfleischburger – ohne Gentechnik, Soja oder Gluten. Ein Beyond Meat-Patty, belegt mit Cheddar-Käse, Salat, Tomate, roter Zwiebel, Gewürzgurken und hausgemachter Mayo, serviert auf einem Pfefferbrötchen.</p>
					</div>
					<div class="burger-item">
						<h4>Pizza Burger</h4>
						<p>Wir haben den Pizza Burger auf das nächste Level gehoben! Unser legendäres Beef-Patty, belegt mit Peperoni, Mozzarella, Marinara, grünen Paprika, sautierten Champignons und Tomaten, serviert auf einem umgedrehten Käsebrötchen, das wir in eine Mini-Pizza verwandelt haben! Das müssen Sie sehen, um es zu glauben!</p>
					</div>
					<div class="burger-item">
						<h4>Golden State Avocado Chicken Burger</h4>
						<p>Gegrilltes Hähnchenbrustfilet, belegt mit Schweizer Käse, Avocado, gerösteter Knoblauch-Mayo, Bacon, Alfalfa-Sprossen, Salat, roten Zwiebeln und Tomaten auf einem Focaccia-Brötchen.</p>
					</div>
					<div class="burger-item">
						<h4>Donut Burger</h4>
						<p>Unser legendäres Beef-Patty, belegt mit Cheddar-Käse und kandiertem Ahorn-Bacon, serviert zwischen zwei glasierten Donuts.</p>
					</div>
					<div class="burger-item">
						<h4>Perogy Burger</h4>
						<p>Unser legendäres Beef-Patty, belegt mit Cheddar-Käse, Bacon, Cheddar-Käse-Perogies, knusprigen Zwiebelringen, Sauerrahm und Frühlingszwiebeln auf einem Zwiebelbrötchen.</p>
					</div>
					<div class="burger-item">
						<h4>Donair Burger</h4>
						<p>Unser legendäres Beef-Patty, reichlich belegt mit geschnittenem Donair-Fleisch, Mozzarella-Käse, süßer Sauce, Salat, Tomaten und roten Zwiebeln auf zwei getoasteten Mini-Pitas. Serviert mit einer Seite süßer Donair-Sauce.</p>
					</div>
					<div class="burger-item">
						<h4>Mushroom “Swiss Miss” Burger</h4>
						<p>Unser legendäres Beef-Patty, belegt mit hausgemachter sautierter Champignon-Soße, Emmentaler, knusprigen Zwiebelringen und Mayonnaise auf einem Zwiebelbrötchen.</p>
					</div>
					<div class="burger-item">
						<h4>Bacon Cheeseburger</h4>
						<p>Ein Brezelbrötchen, belegt mit unserem legendären Beef-Patty, Cheddar-Käse, Bacon, Salat, Tomaten, roten Zwiebeln und hausgemachter Mayo.</p>
					</div>
					<div class="burger-item">
						<h4>Heavy Hitter Cheeseburger</h4>
						<p>Kein gewöhnlicher Cheeseburger! Ein extra großes Beef-Patty, beladen mit amerikanischem, Schweizer, Cheddar und Mozzarella-Käse, Gewürzgurken, grünem Relish, Mayonnaise und Senf auf einem Brioche-Brötchen.</p>
					</div>
					<div class="burger-item">
						<h4>Buffalo Chicken Burger</h4>
						<p>Ein paniertes Hähnchenbrustfilet, überzogen mit unserer hausgemachten Buffalo-Sauce, beladen mit Salat und Tomaten auf einem Brioche-Brötchen. Serviert mit einer Seite Blauschimmelkäse-Dressing.</p>
					</div>
					<div class="burger-item">
						<h4>All-Day Breakfast Burger</h4>
						<p>Unser legendäres Beef-Patty, belegt mit einem Spiegelei, Cheddar-Käse, Bacon, Salat, Tomaten und hausgemachter Mayo auf einem Brioche-Brötchen.</p>
					</div>
					<div class="burger-item">
						<h4>The Great One</h4>
						<p>Zwei unserer legendären Beef-Patties, doppelt Cheddar, doppelt Bacon, karamellisierte Zwiebeln, ein Spiegelei und hausgemachte Mayo, serviert in einem Dinkel-Sesam Bun... es gibt einen Grund, warum wir ihn "The Great One" nennen!</p>
					</div>
					<div class="burger-item">
						<h4>Falafel</h4>
						<p>Falafelbällchen, Tzatziki, Hummus, Alfalfa-Sprossen, eingelegte rote Zwiebeln, gewürfelte Tomaten und gewürfelte Gurken, eingewickelt in ein gegrilltes Pita.</p>
					</div>
					<div class="burger-item">
						<h4>PBJ</h4>
						<p>Jedermanns Lieblingssandwich mit einem Twist: Unser legendäres Beef-Patty, cremige Erdnussbutter, Himbeermarmelade, Bacon, Jalapeño Jack Käse und Salat auf einem Brioche-Brötchen.</p>
					</div>
					<div class="burger-item">
						<h4>The Heat</h4>
						<p>Unser legendäres Beef-Patty, verfeinert mit feuriger Inferno-Mayo, Jalapeño Jack Käse, würzigen Zwiebelringen, Salat und Tomaten auf einem Pfefferbrötchen. Abgerundet mit einem mit Käse gefüllten Jalapeño.</p>
					</div>
					<div class="burger-item">
						<h4>The Augusta</h4>
						<p>Ein gegrilltes Hähnchenbrustfilet, überzogen mit rauchiger BBQ-Sauce, belegt mit Cheddar-Käse, Bacon, hausgemachter Mayo, Salat, Tomaten und karamellisierten Zwiebeln auf einem Brioche-Brötchen.</p>
					</div>
					<div class="burger-item">
						<h4>Mac & Cheese Burger</h4>
						<p>Unser legendäres Beef-Patty, belegt mit einem hausgemachten, frittieren Macaroni-and-Cheese-Patty, würzigem Ketchup, Bacon, amerikanischem Käse, Salat und Tomaten auf einem Käsebrötchen.</p>
					</div>
				</div>
			</div>
		</section>

		<section id="wings">
			<div class="inner-container">
				<div>
					<h2>Wings</h2>
					<p>
						Mit über 30 verschiedenen einzigartigen Saucen und Rubs verfeinert, sind diese Wings ein scharfer Genuss!<br>
						<br>
						Alle unsere traditionellen, knochenlosen oder veganen Wings werden mit Karotten, Sellerie und Ranch-Dip serviert.
					</p>
				</div>
				<div>
					<h3>Saucen</h3>
					<ul>
						<li>Inferno</li>
						<li>Buffalo</li>
						<li>Honey Hot</li>
						<li>Franks Red Hot</li>
						<li>Hot Cajun Salt & Pepper</li>
						<li>Extreme BBQ</li>
						<li>Nashville Hot</li>
						<li>Truffalo Buffalo</li>
						<li>Chili Lime</li>
						<li>Spicy Dill</li>
						<li>Honey Sriracha</li>
						<li>Buffalo Garlic Parmesan</li>
						<li>Hot Teriyaki</li>
						<li>Whisky BBQ</li>
					</ul>
				</div>
				<div>
					<ul>
						<li>Truffalo Buffalo Garlic Parmesan</li>
						<li>Creamy Dill Pickle</li>
						<li>Sweet Chili</li>
						<li>Korean Garlic Soy</li>
						<li>Smoky BBQ</li>
						<li>Teriyaki</li>
						<li>Coca Cola BBQ</li>
						<li>Honey Garlic</li>
						<li>Garlic Parmesan</li>
						<li>Cinnamon Bun</li>
						<li>PBJ</li>
					</ul>
				</div>
				<div>
					<h3>Rubs</h3>
					<ul>
						<li>Ghost Pepper</li>
						<li>Franks Red Hot Rub</li>
						<li>Fiery Roasted Garlic Habanero Rub</li>
						<li>Salt & Pepper Rub</li>
						<li>Cajun Salt & Pepper Rub</li>
						<li>Taco Rub</li>
						<li>Lemon Pepper Rub</li>
						<li>Applewood Smoked Rub</li>
						<li>Maple Bacon Rub</li>
					</ul>
				</div>
			</div>

			<div id="heatmeter" class="inner-container">
				<h2>Heatmeter <span>Hältst du es aus?</span></h2>
				<div id="heatmeter-holder">
					<div class="heat slide">
						<p>Inferno</p>
						<span>WARNUNG: Diese Wings sind so scharf, dass du vielleicht mit der Schärfe nicht klarkommst. Beschwere dich nicht bei uns, wenn sie dich zum Weinen bringen!</span>
					</div>
					<div class="heat slide">
						<p>Buffalo</p>
						<span>Best Burger's Buffalo Sauce. Noch Fragen?</span>
					</div>
					<div class="heat slide">
						<p>Ghost Pepper</p>
						<span>Hot AF!</span>
					</div>
					<div class="heat slide">
						<p>Honey Hot</p>
						<span>Perfekte Kombination aus scharf und süß.</span>
					</div>
					<div class="heat slide">
						<p>Franks Red Hot</p>
						<span>Bringe den Geschmack in die rote Zone.</span>
					</div>
					<div class="heat slide">
						<p>Franks Red Hot Rub</p>
						<span>Original Red Hot Geschmack ohne die Sauerei.</span>
					</div>
					<div class="heat slide">
						<p>Hot Cajun Salt & Pepper</p>
						<span>Salz, Pfeffer und alles Schöne an Gewürzen.</span>
					</div>
					<div class="heat slide">
						<p>Fiery Roasted Garlic Habanero Rub</p>
						<span>Gerösteter Habanero, vermischt mit Knoblauch für den perfekten Kick für die Geschmacksnerven.</span>
					</div>
					<div class="heat slide">
						<p>Extreme BBQ</p>
						<span>Es ist einfach: eine SEHR SCHARFE und köstliche barbecue-artige Sauce!</span>
					</div>
					<div class="heat slide">
						<p>Nashville Hot</p>
						<span>Buttrige scharfe Sauce. Vorsicht vor dem Kick am Ende!</span>
					</div>
					<div class="heat slide">
						<p>Truffalo Buffalo</p>
						<span>Die Kombination unserer hausgemachten Buffalo-Sauce mit Trüffelöl hebt diese Sauce auf die nächste Stufe des Geschmacks.</span>
					</div>
					<div class="heat slide">
						<p>Chili Lime</p>
						<span>Würzig mit einem Hauch von Zitrus.</span>
					</div>
					<div class="heat slide">
						<p>Salt & Pepper Rub</p>
						<span>Meersalz und grob gemahlener Pfeffer.</span>
					</div>
					<div class="heat slide">
						<p>Spicy Dill</p>
						<span>Gurkensaft, Dill und Frank's Red Hot Trockenrub.</span>
					</div>
					<div class="heat slide">
						<p>Honey Sriracha</p>
						<span>Süße Schärfe!</span>
					</div>
					<div class="heat slide">
						<p>Buffalo Garlic Parmesan</p>
						<span>Eine Mischung aus unserer Buffalo-Sauce und Knoblauch-Parmesan-Sauce.</span>
					</div>
					<div class="heat slide">
						<p>Hot Teriyaki</p>
						<span>Mit würziger Teriyaki-Sauce glasiert.</span>
					</div>
					<div class="heat slide">
						<p>Whisky BBQ</p>
						<span>Das einzige Mal, dass die Kombination von Bourbon und BBQ gut ausgegangen ist.</span>
					</div>
					<div class="heat slide">
						<p>Truffalo Buffalo Garlic Parmesan</p>
						<span>Eine magische Mischung aus unserer Truffalo Buffalo und Knoblauch-Parmesan Sauce.</span>
					</div>
					<div class="heat slide">
						<p>Creamy Dill Pickle</p>
						<span>Eine köstliche hausgemachte cremige Dillsauce mit einem Hauch von Gurke.</span>
					</div>
					<div class="heat slide">
						<p>Sweet Chili</p>
						<span>Süß und klebrig mit einem Kick!</span>
					</div>
					<div class="heat slide">
						<p>Korean Garlic Soy</p>
						<span>Eine Mischung aus Sojasauce, braunem Zucker, Knoblauch und Pfeffer.</span>
					</div>
					<div class="heat slide">
						<p>Smoky BBQ</p>
						<span>Genau wie man es über dem Lagerfeuer machen würde, wenn jemand jemals Wings auf diese Weise gemacht hätte.</span>
					</div>
					<div class="heat slide">
						<p>Teriyaki</p>
						<span>Mit süßer Teriyaki-Sauce glasiert.</span>
					</div>
					<div class="heat slide">
						<p>Cajun Salt & Pepper Rub</p>
						<span>Cayennepfeffer, Meersalz und grob gemahlener Pfeffer.</span>
					</div>
					<div class="heat slide">
						<p>Taco Rub</p>
						<span>Wings, die wie ein Taco schmecken!</span>
					</div>
					<div class="heat slide">
						<p>Coca Cola BBQ</p>
						<span>Das weltweit beliebteste Getränk mit einem Hauch von BBQ.</span>
					</div>
					<div class="heat slide">
						<p>Lemon Pepper Rub</p>
						<span>Zitronenschale und grob gemahlener Pfeffer.</span>
					</div>
					<div class="heat slide">
						<p>Applewood Smoked Rub</p>
						<span>Süß, herzhaft, rauchig und kräftig.</span>
					</div>
					<div class="heat slide">
						<p>Honey Garlic</p>
						<span>Ein süßer und klebriger Klassiker.</span>
					</div>
					<div class="heat slide">
						<p>Maple Bacon Rub</p>
						<span>Süß und rauchig.</span>
					</div>
					<div class="heat slide">
						<p>Garlic Parmesan</p>
						<span>Cremig, käsig und reichlich Knoblauch.</span>
					</div>
					<div class="heat slide">
						<p>PBJ</p>
						<span>Dein Lieblingssandwich in eine Wings Sauce verwandelt!</span>
					</div>
					<div class="heat slide">
						<p>Cinnamon Bun</p>
						<span>Hausgemachte klebrige süße Zimtsauce.</span>
					</div>
				</div>
			</div>
		</section>

		<section id="sides">
			<div class="inner-container">
				<h2>Fries & Sides</h2>
				<div>
					<ul>
						<li>House Fries</li>
						<li>Tater Tots</li>
						<li>Onion Rings</li>
					</ul>
					<ul>
						<li>Curly Fries</li>
						<li>Sweet Potato Fries</li>
					</ul>
				</div>
			</div>
		</section>

		<section id="soups-salads">
			<div class="inner-container">
				<h2>Suppen & Salate</h2>
				<div>
					<div>
						<h3>Salate</h3>
						<div>
							<h4>Ty Cobb Salat</h4>
							<p>Gegrilltes Cajun-Hähnchenbrustfilet serviert mit einer Mischung aus Römersalat und Frühlingssalat, vermengt mit einem Poblano-Ranch-Dressing und garniert mit geschnittenem Ei, Speckwürfel, roten Zwiebeln, Traubentomaten, Blauschimmelkäse, geschnittener Avocado und knusprigen Tortillas.</p>
						</div>
						<div>
							<h4>Baja Taco Salat</h4>
							<p>Mexikanisch gewürztes Hackfleisch, schwarze Bohnen, gelber Mais, Traubentomaten, frische Avocado, gewürfelte rote Zwiebeln, garniert mit geriebenem Cheddar und Mozzarella und serviert auf Römersalat. Vermengt mit unserem hausgemachten Chipotle-Vinaigrette und abgeschlossen mit dreifarbigen Tortillachips. Serviert mit Sauerrahm und Salsa an der Seite.</p>
						</div>
						<div>
							<h4>Suppen & Salat Kombi</h4>
							<p>Eine Portion unserer Tagessuppe und Ihre Wahl zwischen einem Madison Square Garden Salat oder einem Caesar Salat. Serviert mit Knoblauchtoast.</p>
						</div>
						<div>
							<h4>Madison Square Garden Salat</h4>
							<p>Gemischte Grüne Salate mit Karotten, Gurken, roten Zwiebeln und Traubentomaten. Serviert mit Ihrer Wahl an Ranch-, Blauschimmelkäse-, Italienischem-, Thousand Island- oder Balsamico-Vinaigrette.</p>
						</div>
						<div>
							<h4>Caesar Salat</h4>
							<p>Frischer Römersalat mit unserem hausgemachten Caesar-Dressing, Speckwürfel, Croutons und Parmesan.</p>
						</div>
					</div>
					<div>
						<h3>Suppen</h3>
						<div>
							<h4>Tagessuppe</h4>
							<p>Fragen Sie Ihren Kellner, welche Suppe heute auf der Karte steht.</p>
						</div>
					</div>
				</div>
			</div>
		</section>

		<section id="desserts">
			<div class="inner-container">
				<h2>Nachspeisen</h2>
				<div>
					<div>
						<h4>Schokoladenexplosion</h4>
						<p>Vier Schichten dekadenter Schokoladenkuchen, getrennt durch reichhaltige Schokoladenglasur. Abgeschlossen mit einem dunklen Schokoladenguss und serviert mit Vanilleeis.</p>
					</div>

					<div>
						<h4>Dessert Burger</h4>
						<p>Ein Schokoladenfudge-Brownie, gestapelt auf Minzschokoladeneis und serviert zwischen zwei glasierten Donuts. Garniert mit Mini-Marshmallows und Streuseln und abgeschlossen mit Karamell-, Himbeersoße und Maraschinokirschen.</p>
					</div>
				</div>
			</div>
		</section>

		<section id="reserve">
			<div class="inner-container">
				<h2>Reservieren</h2>
				<p>
					Täglich von 11:00 bis 00:00 Uhr geöffnet!<br>
					<br>
					<br>
					ACHTUNG, aufgrund der hohen Nachfrage:<br>
					<br>
					Wir bitten um Ihr Verständnis, dass jede Person nur einmal pro Woche reservieren kann.<br>
					Beachten Sie bitte ausserdem dass die Reservierung nur in halbstündigen Blöcken möglich ist.
				</p>
				<form id="reservation-form" method="post">
					<div id="restaurantMap">
						<input type="text" id="resTag" name="resTag" placeholder="Datum wählen" required>
						<select name="resHour" required>
							<option value="">Stunde wählen</option>
							<?php for ($hour = 11; $hour <= 23; $hour++): ?>
								<option value="<?= $hour ?>"><?= str_pad($hour, 2, '0', STR_PAD_LEFT) ?>:00</option>
							<?php endfor; ?>
						</select>
						<select name="resMinute" required>
							<option value="">Minute wählen</option>
							<option value="00">00</option>
							<option value="30">30</option>
						</select>
						<button type="button" id="checkAvailabilityBtn" class="btn-secondary">Freie Tische?</button>
						<div>
							<img src="media/restaurant-map-removebg-preview.png" alt="Restaurant Map">
							<?php for ($i = 1; $i <= 8; $i++): ?>
								<button type="button" id="table<?= $i ?>" class="table-button"><span>Tisch </span><?= $i ?></button>
							<?php endfor; ?>
							<input type="hidden" id="selectedTable" name="selectedTable">
						</div>
						<input type="text" name="name" placeholder="Name" required>
						<input type="email" name="email" placeholder="E-Mail" required>
						<input type="tel" name="phone" placeholder="Telefon" required>
						<div class="g-recaptcha" data-sitekey=""></div>
						<input type="submit" value="Reservieren" class="btn-primary hvr-outline-out">
					</div>
				</form>
				<?php echo ($msg); ?>
			</div>
		</section>

		<section id="locate">
			<div class="inner-container">
				<h2>Anfahrt & Kontakt</h2>
				<div>
					<p>
						<strong>Öffnungszeiten:</strong><br>
						Täglich von 11:00 - 00:00 Uhr
					</p>
					<p>
						Muster-Weg 17<br>
						4020 Linz<br>
						+43 123 123456
					</p>
					<iframe src="https://www.google.com/maps/embed?pb=!1m14!1m12!1m3!1d9530.480718231542!2d14.280420034130294!3d48.305108947331505!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!5e0!3m2!1sen!2sat!4v1725220045242!5m2!1sen!2sat" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
				</div>
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
	<script>
		(function() {
			const prefersDarkScheme = window.matchMedia('(prefers-color-scheme: dark)').matches;
			const recaptchaElement = document.querySelector('.g-recaptcha');

			if (prefersDarkScheme) {
				recaptchaElement.setAttribute('data-theme', 'dark');
			} else {
				recaptchaElement.setAttribute('data-theme', 'light');
			}
		})();
	</script>
</body>

</html>
