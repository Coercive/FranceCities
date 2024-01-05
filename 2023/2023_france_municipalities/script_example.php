<?php
/**
 * FRANCE CITIES EXAMPLE SCRIPT
 *
 * SEE THE README.md
 *
 * @link https://github.com/Coercive/FranceCities
 *
 * @author Anthony Moral <contact@coercive.fr>
 * @copyright 2024 Anthony Moral
 * @license MIT
 */
namespace Coercive\FranceCities;

use Coercive\Utility\Csv\Importer;
use Coercive\Utility\Slugify\Slugify;

ini_set('memory_limit', '-1');
set_time_limit(0);

/**
 * Load Slugify
 *
 * @link https://github.com/Coercive/Slugify
 */
$Slugify = new Slugify;

# LA POSTE CSV FILE
$laposte_hexasmal = '/la_poste/laposte_hexasmal_gps.csv';

# INSEE CSV FILE
$insee_com = '/insee/cities/v_commune_2023.csv';

# Read INSEE COM data .csv (TYPECOM,COM,REG,DEP,CTCD,ARR,TNCC,NCC,NCCENR,LIBELLE,CAN,COMPARENT)
# TYPECOM : [COM commune] [COMA commune fusion associée] [ARM Arrondissement] [COMD commune déléguée]
$importer = new Importer($insee_com, ',');
$importer->parseHeader();
$importer->onlyHeader([
	'TYPECOM', # Type de commune insee (COMA = associée)
	'COM', # Code commune insee
	'REG', # Code région
	'DEP', # Code département
	'CTCD', # Code de la collectivité territoriale ayant les compétences départementales
	'ARR', # Code arrondissement
	'TNCC', # Type de nom en clair
	'NCC', # Nom normalisé majuscules
	'NCCENR', # Nom en clair (typographie riche)
	'LIBELLE', # Nom en clair (typographie riche) avec article
	'CAN', # Code canton. Pour les communes « multi-cantonales », code décliné de 99 à 90 (pseudo-canton) ou de 89 à 80 (communes nouvelles)
	'COMPARENT', # Code de la commune parente pour les arrondissements municipaux et les communes associées ou déléguées.
]);
$insee_com_data = $importer->get();

# Parse INSEE COM data
$towns = [];
foreach ($insee_com_data as $com) {
	if(!$code = $com['COM']) {
		continue;
	}
	if(isset($towns[$code])) {
		die('Town already exist with code : ' . $code);
	}

	$towns[$com['TYPECOM'].$code] = [
		'TYPE' => $com['TYPECOM'],
		'REF_INSEE' => $code,
		'TYPE_NAME' => intval($com['TNCC']),
		'NORMALIZED_NAME' => trim($com['NCC']),
		'NAME' => trim($com['NCCENR']),
		'FULLNAME' => trim($com['LIBELLE']),
		'SLUG' => $Slugify->clean(trim($com['LIBELLE'])),
		'NORMALIZED' => $Slugify->clean(trim($com['LIBELLE']), ''),
		'SEARCHFULLTEXT' => $Slugify->searchSqlCleaner(trim($com['LIBELLE'])),
		'DEPARTMENT' => $com['DEP'],
		'REGION' => $com['REG'],
		'DISTRICT_OF' => $com['COMPARENT'],
	];
}

# Read LA POSTE data .csv
$importer = new Importer($laposte_hexasmal, ',');
$importer->parseHeader();
$importer->onlyHeader([
	'#Code_commune_INSEE',
	'Nom_de_la_commune',
	'Code_postal',
	'Libellé_d_acheminement',
	'Ligne_5', # Communes rattachées - Associated municipality
	'_contours_commune.geometry',
]);
$laposte_data = $importer->get();

# Parse La POSTE data
$coordinates = [];
$associated = [];
foreach ($laposte_data as $lp) {
	if($code = $lp['#Code_commune_INSEE']) {

		# Communes rattachées - Associated municipality
		if(!empty($lp['Ligne_5'])) {
			$associated[] = [
				'REF_INSEE' => $code,
				'NAME' => trim($lp['Nom_de_la_commune']),
				'OTHER_NAME' => trim($lp['Libellé_d_acheminement']),
				'OTHER_NAME_NORMALIZED' => $Slugify->clean(trim($lp['Libellé_d_acheminement']), ''),
				'ASSOCIATED' => trim($lp['Ligne_5']),
				'ASSOCIATED_NORMALIZED' => $Slugify->clean(trim($lp['Ligne_5']), ''),
				'GEOMETRY_GPS' => $lp['_contours_commune.geometry'],
				'ZIP_CODES' => [
					$lp['Code_postal']
				],
			];
			continue;
		}

		# Additionnals zip codes for the same city
		if(isset($coordinates[$code])) {
			if(!in_array($lp['Code_postal'], $coordinates[$code]['ZIP_CODES'])) {
				$coordinates[$code]['ZIP_CODES'][] = $lp['Code_postal'];
				continue;
			}
		}
		# Add city
		else {
			$coordinates[$code] = [
				'REF_INSEE' => $code,
				'NAME' => $lp['Nom_de_la_commune'],
				'GEOMETRY_GPS' => $lp['_contours_commune.geometry'],
				'ZIP_CODES' => [
					$lp['Code_postal']
				],
			];
		}
	}
}

# MERGE & SAVE DATAS
foreach ($towns as $town) {

	$refInsee = $town['REF_INSEE'];

	$coordinate = $coordinates[$refInsee] ?? [];
	if(!$coordinate) {
		if($town['TYPE'] === 'COM') {
			foreach ($associated as $associat) {
				if($associat['OTHER_NAME'] === $town['NORMALIZED_NAME'] || $associat['OTHER_NAME_NORMALIZED'] === $town['NORMALIZED']) {
					$coordinate = $associat;
				}
			}
			if(!$coordinate && false !== strpos($town['NORMALIZED_NAME'], 'SAINT')) {
				$abbrName = str_replace('SAINT', 'ST', $town['NORMALIZED_NAME']);
				foreach ($associated as $associat) {
					if($associat['OTHER_NAME'] === $abbrName) {
						$coordinate = $associat;
					}
				}
			}
			# Some wrong names in La Poste file...
			if(!$coordinate) {
				$errorName = $town['NORMALIZED_NAME'];

				if($errorName === 'DEVOLUY') {
					$errorName = 'LE DEVOLUY';
				}
				if($errorName === 'SAINT LAURENT LES BAINS LAVAL D AURELLE') {
					$errorName = 'ST LAURENT BAINS LAVAL D AURELLE';
				}
				if($errorName === 'MESNIL SAINT JEAN') {
					$errorName = 'LE MESNIL ST JEAN';
				}

				foreach ($associated as $associat) {
					if($associat['OTHER_NAME'] === $errorName) {
						$coordinate = $associat;
					}
				}
			}
			# District Lyon / Paris => load main district
			if($town['NORMALIZED_NAME'] === 'LYON') {
				$coordinate = $coordinates[69381] ?? [];
			}
			if($town['NORMALIZED_NAME'] === 'PARIS') {
				$coordinate = $coordinates[75101] ?? [];
			}
			if(!$coordinate) {
				error_log(print_r("Type commune : Coordonnées non-trouvées : $refInsee - $town[NORMALIZED_NAME]", true));
				continue;
			}
		}
		elseif($town['TYPE'] === 'ARR') {
			error_log(print_r('Type arrondissement, coordonnées non-trouvées', true));
		}
		elseif($town['TYPE'] === 'COMA' || $town['TYPE'] === 'COMD') {
			foreach ($associated as $associat) {
				if($associat['ASSOCIATED_NORMALIZED'] === $town['NORMALIZED'] || $associat['ASSOCIATED'] === $town['NORMALIZED_NAME']) {
					$coordinate = $associat;
				}
			}
			if(!$coordinate && false !== strpos($town['NORMALIZED_NAME'], 'SAINT')) {
				$abbrName = str_replace('SAINT', 'ST', $town['NORMALIZED_NAME']);
				foreach ($associated as $associat) {
					if($associat['ASSOCIATED'] === $abbrName) {
						$coordinate = $associat;
					}
				}
			}
			# Some wrong names in La Poste file...
			if(!$coordinate) {
				$errorName = $town['NORMALIZED_NAME'];

				if($errorName === 'TREFFORT') {
					$errorName = 'TREFFORT CUISIAT';
				}
				if($errorName === 'AUTELS SAINT BAZILE') {
					$errorName = 'LES AUTELS ST BAZILE';
				}
				if($errorName === 'SAINT GERMAIN DE TALLEVENDE LA LANDE VAUMONT') {
					$errorName = 'ST GERMAIN DE TALLEVENDE';
				}
				if($errorName === 'SAINTE ALVERE') {
					$errorName = 'STE ALVERE ST LAURENT LES BATONS';
				}
				if($errorName === 'BRETEUIL') {
					$errorName = 'BRETEUIL SUR ITON';
				}
				if($errorName === 'BLEURY SAINT SYMPHORIEN') {
					$errorName = 'ST SYMPHORIEN LE CHATEAU';
				}
				if($errorName === 'BRIGNOGAN PLAGES') {
					$errorName = 'BRIGNOGAN PLAGE';
				}
				if($errorName === 'CHAPELLE SAINT SAUVEUR') {
					$errorName = 'LA CHAPELLE ST SAUVEUR';
				}
				if($errorName === 'CHAPELLE SAINT FLORENT') {
					$errorName = 'LA CHAPELLE ST FLORENT';
				}
				if($errorName === 'CHEMILLE') {
					$errorName = 'CHEMILLE MELAY';
				}
				if($errorName === 'CHENEHUTTE TREVES CUNAULT') {
					$errorName = 'CHENEHUTTE LES TUFFEAUX';
				}
				if($errorName === 'CHERBOURG OCTEVILLE') {
					$errorName = 'CHERBOURG';
				}
				if($errorName === 'FRETTES') {
					$errorName = 'CHAMPLITTE LA VILLE';
				}
				if($errorName === 'PAUTAINES AUGEVILLE') {
					$errorName = 'PAUTAINES';
				}
				if($errorName === 'ROC SAINT ANDRE') {
					$errorName = 'LE ROC ST ANDRE';
				}
				if($errorName === 'BOURG SAINT LEONARD') {
					$errorName = 'LE BOURG ST LEONARD';
				}
				if($errorName === 'PASSAIS') {
					$errorName = 'PASSAIS LA CONCEPTION';
				}
				if($errorName === 'EBERBACH WOERTH') {
					$errorName = 'EBERBACH';
				}
				if($errorName === 'NEHWILLER PRES WOERTH') {
					$errorName = 'NEHWILLER';
				}
				if($errorName === 'BERNWILLER') {
					$errorName = 'AMMERTZWILLER';
				}
				if($errorName === 'MONTROND') {
					$coordinate = $coordinates[73013] ?? [];
				}
				if($errorName === 'NESLES') {
					$errorName = 'NESLES LA GILBERDE';
				}
				if($errorName === 'CHAPELLE SAINT ETIENNE') {
					$errorName = 'LA CHAPELLE ST ETIENNE';
				}
				if($errorName === 'PUY SAINT BONNET') {
					$errorName = 'LE PUY ST BONNET';
				}

				if(!$coordinate) {
					foreach ($associated as $associat) {
						if($associat['ASSOCIATED'] === $errorName) {
							$coordinate = $associat;
						}
					}
				}
			}
		}
	}
	if(!$coordinate) {
		error_log(print_r("Coordonnées non-trouvées : $refInsee - $town[NORMALIZED_NAME]", true));
	}

	# Zip codes
	$zipCodes = implode(',', $coordinate['ZIP_CODES'] ?? []);

	# New GPS (contours)
	$latitude = 0.0;
	$longitude = 0.0;
	$geometry = '';
	if($coordinate && $coordinate['GEOMETRY_GPS']) {
		$geometry = $coordinate['GEOMETRY_GPS'];
		$json = json_decode($geometry, true) ?? [];
		$gps = $json['coordinates'] ?? [];
		foreach ($gps as $gp) {
			$longitudes = 0;
			$latitudes = 0;
			$count = 0;
			if($json['type'] === 'Polygon') {
				$count = count($gp);
				foreach ($gp as $latlong) {
					$longitudes += $latlong[0] ?? 0.0;
					$latitudes += $latlong[1] ?? 0.0;
				}
			}
			elseif($json['type'] === 'MultiPolygon') {
				foreach ($gp as $g) {
					$count += count($g);
					foreach ($g as $latlong) {
						$longitudes += $latlong[0] ?? 0.0;
						$latitudes += $latlong[1] ?? 0.0;
					}
				}
			}
			else {
				error_log(print_r($json, true));
				throw new \Exception("Un bidule a été rencontré : $json[type]");
			}
			$longitude = floatval($longitudes / $count);
			$latitude = floatval($latitudes / $count);
		}
	}

	# Lost town
	if($town['NORMALIZED_NAME'] === 'LES TROIS LACS' && !$longitude && !$latitude) {
		$latitude = '49.227529';
		$longitude = '1.350426';
	}

	# Add basic info
	if($town['TYPE'] === 'COMA' || $town['TYPE'] === 'COMD') {
		$realTown = $towns['COM' . $town['DISTRICT_OF']] ?? [];
		if($realTown) {
			$town['DEPARTMENT'] = $realTown['DEPARTMENT'];
			$town['REGION'] = $realTown['REGION'];
		}
	}

	# Save the data (use your database object, or save in csv, or whatever else you want...)
	$entry = [
		'UNIQ_REF_INSEE' => $town['TYPE'] . $town['REF_INSEE'],
		'REF_INSEE' => $town['REF_INSEE'],
		'NORMALIZED_NAME' => $town['NORMALIZED_NAME'],
		'NAME' => $town['NAME'],
		'FULLNAME' => $town['FULLNAME'],
		'SEARCHFULLTEXT' => $town['SEARCHFULLTEXT'],
		'SLUG' => $town['SLUG'],
		'NORMALIZED' => $town['NORMALIZED'],
		'DEPARTMENT' => $town['DEPARTMENT'],
		'REGION' => $town['REGION'],
		'IS_DISTRICT' => $town['TYPE'] === 'ARM' ? '1' : '0',
		'DISTRICT_OF' => $town['DISTRICT_OF'],
		'ASSOCIATED_MUNICIPALITY' => $town['TYPE'] === 'COMA' ? '1' : '0',
		'DELEGATED_MUNICIPALITY' => $town['TYPE'] === 'COMD' ? '1' : '0',
		'ZIP_CODES' => $zipCodes,
		'LATITUDE' => number_format($latitude, 14, '.', ''),
		'LONGITUDE' => number_format($longitude, 14, '.', ''),
		'GEOMETRY_GPS' => $geometry,
	];
}

die('OK ' . time());
