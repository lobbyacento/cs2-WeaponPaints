<?php
require_once 'class/config.php';
require_once 'class/database.php';
require_once 'steamauth/steamauth.php';
require_once 'class/utils.php';

$db = new DataBase();
if (isset($_SESSION['steamid'])) {

	$steamid = $_SESSION['steamid'];

	$weapons = UtilsClass::getWeaponsFromArray();
	$skins = UtilsClass::skinsFromJson();
    $querySelected = $db->select("
        SELECT `weapon_defindex`, MAX(`weapon_paint_id`) AS `weapon_paint_id`, MAX(`weapon_wear`) AS `weapon_wear`, MAX(`weapon_seed`) AS `weapon_seed`
        FROM `wp_player_skins`
        WHERE `steamid` = :steamid
        GROUP BY `weapon_defindex`, `steamid`
    ", ["steamid" => $steamid]);
	$selectedSkins = UtilsClass::getSelectedSkins($querySelected);
	$selectedKnife = $db->select("SELECT * FROM `wp_player_knife` WHERE `wp_player_knife`.`steamid` = :steamid LIMIT 1", ["steamid" => $steamid]);
	$knifes = UtilsClass::getKnifeTypes();
	$knifeDefindexes = array_keys(array_filter($knifes, fn($k, $i) => $i !== 0, ARRAY_FILTER_USE_BOTH));

	if (isset($_POST['forma'])) {
		$ex = explode("-", $_POST['forma']);

		if ($ex[0] == "knife") {
			$knifeDefindex = (int) $ex[1];
			$db->query("INSERT INTO `wp_player_knife` (`steamid`, `knife`, `weapon_team`) VALUES(:steamid, :knife, 2) ON DUPLICATE KEY UPDATE `knife` = :knife", ["steamid" => $steamid, "knife" => $knifes[$knifeDefindex]['weapon_name']]);
			$db->query("INSERT INTO `wp_player_knife` (`steamid`, `knife`, `weapon_team`) VALUES(:steamid, :knife, 3) ON DUPLICATE KEY UPDATE `knife` = :knife", ["steamid" => $steamid, "knife" => $knifes[$knifeDefindex]['weapon_name']]);
			header("Location: {$_SERVER['PHP_SELF']}?knife={$knifeDefindex}");
			exit;
		} elseif ($ex[0] == "knifeskin") {
			$defindex = (int) $ex[1];
			$paintId = (int) $ex[2];
			$wear = 0.0;
			$seed = 0;
			if (array_key_exists($defindex, $selectedSkins)) {
				$db->query("UPDATE wp_player_skins SET weapon_paint_id = :weapon_paint_id, weapon_wear = :weapon_wear, weapon_seed = :weapon_seed WHERE steamid = :steamid AND weapon_defindex = :weapon_defindex", ["steamid" => $steamid, "weapon_defindex" => $defindex, "weapon_paint_id" => $paintId, "weapon_wear" => $wear, "weapon_seed" => $seed]);
			} else {
				$db->query("INSERT INTO wp_player_skins (`steamid`, `weapon_defindex`, `weapon_paint_id`, `weapon_wear`, `weapon_seed`, `weapon_team`) VALUES (:steamid, :weapon_defindex, :weapon_paint_id, :weapon_wear, :weapon_seed, 2)", ["steamid" => $steamid, "weapon_defindex" => $defindex, "weapon_paint_id" => $paintId, "weapon_wear" => $wear, "weapon_seed" => $seed]);
				$db->query("INSERT INTO wp_player_skins (`steamid`, `weapon_defindex`, `weapon_paint_id`, `weapon_wear`, `weapon_seed`, `weapon_team`) VALUES (:steamid, :weapon_defindex, :weapon_paint_id, :weapon_wear, :weapon_seed, 3)", ["steamid" => $steamid, "weapon_defindex" => $defindex, "weapon_paint_id" => $paintId, "weapon_wear" => $wear, "weapon_seed" => $seed]);
			}
			header("Location: {$_SERVER['PHP_SELF']}");
			exit;
		} else {
			if (array_key_exists($ex[1], $skins[$ex[0]]) && isset($_POST['wear']) && $_POST['wear'] >= 0.00 && $_POST['wear'] <= 1.00 && isset($_POST['seed'])) {
				$wear = floatval($_POST['wear']); // wear
				$seed = intval($_POST['seed']); // seed
				if (array_key_exists($ex[0], $selectedSkins)) {
					$db->query("UPDATE wp_player_skins SET weapon_paint_id = :weapon_paint_id, weapon_wear = :weapon_wear, weapon_seed = :weapon_seed WHERE steamid = :steamid AND weapon_defindex = :weapon_defindex", ["steamid" => $steamid, "weapon_defindex" => $ex[0], "weapon_paint_id" => $ex[1], "weapon_wear" => $wear, "weapon_seed" => $seed]);
				} else {
					$db->query("INSERT INTO wp_player_skins (`steamid`, `weapon_defindex`, `weapon_paint_id`, `weapon_wear`, `weapon_seed`, `weapon_team`) VALUES (:steamid, :weapon_defindex, :weapon_paint_id, :weapon_wear, :weapon_seed, 2)", ["steamid" => $steamid, "weapon_defindex" => $ex[0], "weapon_paint_id" => $ex[1], "weapon_wear" => $wear, "weapon_seed" => $seed]);
					$db->query("INSERT INTO wp_player_skins (`steamid`, `weapon_defindex`, `weapon_paint_id`, `weapon_wear`, `weapon_seed`, `weapon_team`) VALUES (:steamid, :weapon_defindex, :weapon_paint_id, :weapon_wear, :weapon_seed, 3)", ["steamid" => $steamid, "weapon_defindex" => $ex[0], "weapon_paint_id" => $ex[1], "weapon_wear" => $wear, "weapon_seed" => $seed]);
				}
			}
		}
		header("Location: {$_SERVER['PHP_SELF']}");
	}
}
?>

<!DOCTYPE html>
<html lang="en"<?php if(WEB_STYLE_DARK) echo 'data-bs-theme="dark"'?>>

<head>
	<meta charset="utf-8">
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
	<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.min.js"></script>
	<link rel="stylesheet" href="style.css">
	<title>CS2 Skins</title>
</head>

<body>

	<?php
	if (!isset($_SESSION['steamid'])) {
	?>
	<header class="site-header">
		<h1>CS2 Skins</h1>
		<div class="sub">Painel de loadout</div>
	</header>
	<main class="login-panel">
		<p>Entre com Steam para configurar suas skins.<br>No servidor use <code>!wp</code> ou <code>!skins</code>.</p>
		<?php loginbutton("rectangle"); ?>
	</main>
	<?php
	} else {
		$actualKnife = $knifes[0];
		$actualKnifeDefindex = 0;
		if ($selectedKnife != null) {
			foreach ($knifes as $knifeKey => $knife) {
				if ($selectedKnife[0]['knife'] == $knife['weapon_name']) {
					$actualKnife = $knife;
					$actualKnifeDefindex = $knifeKey;
					break;
				}
			}
		}

		$knifePickerDefindex = isset($_GET['knife']) ? (int) $_GET['knife'] : null;
		if ($knifePickerDefindex !== null && isset($knifes[$knifePickerDefindex]) && isset($skins[$knifePickerDefindex])) {
	?>
	<header class="site-header">
		<div class="header-row">
			<div>
				<h1><?php echo htmlspecialchars($knifes[$knifePickerDefindex]['paint_name']); ?></h1>
				<div class="sub">Escolha a skin da faca</div>
			</div>
			<a class="btn btn-logout" href="<?php echo $_SERVER['PHP_SELF']; ?>">Voltar</a>
		</div>
	</header>
	<main class="loadout-wrap">
		<div class="skin-picker-grid">
			<?php foreach ($skins[$knifePickerDefindex] as $paintKey => $paint) {
				$isSelected = isset($selectedSkins[$knifePickerDefindex]) && (int) $selectedSkins[$knifePickerDefindex]['weapon_paint_id'] === (int) $paintKey;
			?>
			<form action="" method="POST" class="skin-picker-item<?php echo $isSelected ? ' is-selected' : ''; ?>">
				<input type="hidden" name="forma" value="knifeskin-<?php echo $knifePickerDefindex; ?>-<?php echo $paintKey; ?>">
				<button type="submit" class="skin-picker-btn">
					<img src="<?php echo htmlspecialchars($paint['image_url']); ?>" alt="" class="skin-image">
					<span class="skin-picker-name"><?php echo htmlspecialchars($paint['paint_name']); ?></span>
				</button>
			</form>
			<?php } ?>
		</div>
	</main>
	<?php
		} else {
	?>
	<header class="site-header">
		<div class="header-row">
			<div>
				<h1>Loadout</h1>
				<div class="sub">Skins aplicam ao entrar no servidor</div>
			</div>
			<a class="btn btn-logout" href="<?php echo $_SERVER['PHP_SELF']; ?>?logout">Sair</a>
		</div>
	</header>
	<main class="loadout-wrap">
		<div class="loadout-grid">

		<div class="col-sm-2">
			<div class="card text-center mb-3 knife-card">
				<div class="card-body">
					<?php
					$knifeSkin = null;
					if ($actualKnifeDefindex && isset($selectedSkins[$actualKnifeDefindex])) {
						$paintId = $selectedSkins[$actualKnifeDefindex]['weapon_paint_id'];
						if (isset($skins[$actualKnifeDefindex][$paintId])) {
							$knifeSkin = $skins[$actualKnifeDefindex][$paintId];
						}
					}

					echo "<div class='card-header'>";
					echo "<h6 class='card-title item-name'>Faca</h6>";
					echo "<h5 class='card-title item-name'>" . htmlspecialchars($knifeSkin['paint_name'] ?? $actualKnife['paint_name']) . "</h5>";
					echo "</div>";
					echo "<img src='" . htmlspecialchars($knifeSkin['image_url'] ?? $actualKnife['image_url']) . "' class='skin-image'>";
					?>
				</div>
				<div class="card-footer knife-footer">
					<form action="" method="POST">
						<select name="forma" class="form-control select" onchange="this.form.submit()">
							<option disabled>Tipo de faca</option>
							<?php
							foreach ($knifes as $knifeKey => $knife) {
								$selected = ($selectedKnife && $selectedKnife[0]['knife'] == $knife['weapon_name']) ? ' selected' : '';
								echo "<option{$selected} value=\"knife-{$knifeKey}\">" . htmlspecialchars($knife['paint_name']) . "</option>";
							}
							?>
						</select>
					</form>
					<?php if ($actualKnifeDefindex && isset($skins[$actualKnifeDefindex])) { ?>
					<a class="btn btn-primary btn-block knife-skin-link" href="<?php echo $_SERVER['PHP_SELF']; ?>?knife=<?php echo $actualKnifeDefindex; ?>">Escolher skin</a>
					<?php } ?>
				</div>
			</div>
		</div>

		<?php
		foreach ($weapons as $defindex => $default) {
			if (in_array($defindex, $knifeDefindexes, true)) {
				continue;
			}
		?>
			<div class="col-sm-2">
				<div class="card text-center mb-3">
					<div class="card-body">
						<?php
						if (array_key_exists($defindex, $selectedSkins)) {
							echo "<div class='card-header'>";
							echo "<h5 class='card-title item-name'>{$skins[$defindex][$selectedSkins[$defindex]['weapon_paint_id']]["paint_name"]}</h5>";
							echo "</div>";
							echo "<img src='{$skins[$defindex][$selectedSkins[$defindex]['weapon_paint_id']]['image_url']}' class='skin-image'>";
						} else {
							echo "<div class='card-header'>";
							echo "<h5 class='card-title item-name'>{$default["paint_name"]}</h5>";
							echo "</div>";
							echo "<img src='{$default["image_url"]}' class='skin-image'>";
						}
						?>
					</div>
					<div class="card-footer">
						<form action="" method="POST">
							<select name="forma" class="form-control select" onchange="this.form.submit()" class="SelectWeapon">
								<option disabled>Select skin</option>
								<?php
								foreach ($skins[$defindex] as $paintKey => $paint) {
									if (array_key_exists($defindex, $selectedSkins) && $selectedSkins[$defindex]['weapon_paint_id'] == $paintKey)
										echo "<option selected value=\"{$defindex}-{$paintKey}\">{$paint['paint_name']}</option>";
									else
										echo "<option value=\"{$defindex}-{$paintKey}\">{$paint['paint_name']}</option>";
								}
								?>
							</select>
							<br></br>
							<?php
							$selectedSkinInfo = isset($selectedSkins[$defindex]) ? $selectedSkins[$defindex] : null;
							$steamid = $_SESSION['steamid'];

							if ($selectedSkinInfo) :
							?>
								<button type="button" class="btn btn-primary" data-toggle="modal" data-target="#weaponModal<?php echo $defindex ?>">
									Settings
								</button>
							<?php else : ?>
								<button type="button" class="btn btn-primary" onclick="showSkinSelectionAlert()">
									Settings
								</button>
								<script>
									function showSkinSelectionAlert() {
										alert("You need to select a skin first.");
									}
								</script>
							<?php endif; ?>

					</div>

					<?php
					// wear value 
					$selectedSkinInfo = isset($selectedSkins[$defindex]['weapon_paint_id']) ? $selectedSkins[$defindex] : null;
					$queryWear = $selectedSkins[$defindex]['weapon_wear'] ?? 1.0;
					$initialWearValue = isset($selectedSkinInfo['weapon_wear']) ? $selectedSkinInfo['weapon_wear'] : (isset($queryWear[0]['weapon_wear']) ? $queryWear[0] : 0.0);

					// seed value 
					$querySeed = $selectedSkins[$defindex]['weapon_seed'] ?? 0;
					$initialSeedValue = isset($selectedSkinInfo['weapon_seed']) ? $selectedSkinInfo['weapon_seed'] : 0;
					?>


					<div class="modal fade" id="weaponModal<?php echo $defindex ?>" tabindex="-1" role="dialog" aria-labelledby="weaponModalLabel<?php echo $defindex ?>" aria-hidden="true">
						<div class="modal-dialog" role="document">
							<div class="modal-content">
								<div class="modal-header">
									<h5 class='card-title item-name'>
										<?php
										if (array_key_exists($defindex, $selectedSkins)) {
											echo "{$skins[$defindex][$selectedSkins[$defindex]['weapon_paint_id']]["paint_name"]} Settings";
										} else {
											echo "{$default["paint_name"]} Settings";
										}
										?>
									</h5>
								</div>
								<div class="modal-body">
									<div class="form-group">
										<select class="form-select" id="wearSelect<?php echo $defindex ?>" name="wearSelect" onchange="updateWearValue<?php echo $defindex ?>(this.value)">
											<option disabled>Select Wear</option>
											<option value="0.00" <?php echo ($initialWearValue == 0.00) ? 'selected' : ''; ?>>Factory New</option>
											<option value="0.07" <?php echo ($initialWearValue == 0.07) ? 'selected' : ''; ?>>Minimal Wear</option>
											<option value="0.15" <?php echo ($initialWearValue == 0.15) ? 'selected' : ''; ?>>Field-Tested</option>
											<option value="0.38" <?php echo ($initialWearValue == 0.38) ? 'selected' : ''; ?>>Well-Worn</option>
											<option value="0.45" <?php echo ($initialWearValue == 0.45) ? 'selected' : ''; ?>>Battle-Scarred</option>
										</select>

									</div>
									<div class="row">
										<div class="col-md-6">
											<div class="form-group">
												<label for="wear">Wear:</label>
												<input type="text" value="<?php echo $initialWearValue; ?>" class="form-control" id="wear<?php echo $defindex ?>" name="wear">
											</div>
										</div>
										<div class="col-md-6">
											<div class="form-group">
												<label for="seed">Seed:</label>
												<input type="text" value="<?php echo $initialSeedValue; ?>" class="form-control" id="seed<?php echo $defindex ?>" name="seed" oninput="validateSeed(this)">
											</div>
										</div>
									</div>
								</div>
								<div class="modal-footer">
									<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
									<button type="submit" class="btn btn-danger">Use</button>
									</form>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<script>
				//  wear
				function updateWearValue<?php echo $defindex ?>(selectedValue) {
					var wearInputElement = document.getElementById("wear<?php echo $defindex ?>");
					wearInputElement.value = selectedValue;
				}

				function validateWear(inputElement) {
					inputElement.value = inputElement.value.replace(/[^0-9]/g, '');
				}
				// seed
				function validateSeed(input) {
					// Check entered value
					var inputValue = input.value.replace(/[^0-9]/g, ''); // Just get the numbers

					if (inputValue === "") {
						input.value = 0; // Set to 0 if empty or no numbers
					} else {
						var numericValue = parseInt(inputValue);
						numericValue = Math.min(1000, Math.max(1, numericValue)); // Interval control

						input.value = numericValue;
					}
				}
			</script>
		<?php } ?>
		</div></main>
	<?php
		}
	} ?>
	<footer class="site-footer">
		WeaponPaints · <a href="https://github.com/Nereziel/cs2-WeaponPaints">Nereziel</a>
	</footer>
</body>

</html>
