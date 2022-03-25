<?php
/**
 * CODIFICATION_OF_OVERSEAS_COLLECTIVITIES
 *
 * FRANCE CITIES EXAMPLE SCRIPT
 *
 * SEE THE README.md
 *
 * @link https://github.com/Coercive/FranceCities
 *
 * @author Anthony Moral <contact@coercive.fr>
 * @copyright 2022 Anthony Moral
 * @license MIT
 */

use Coercive\Utility\Csv\Importer;

$cities_to_import = [
	'98711' =>	'Anaa',
	'98712' =>	'Arue',
	'98713' =>	'Arutua',
	'98714' =>	'Bora-Bora',
	# ...
];

# Read LA POSTE data .csv
$importer = new Importer('/laposte_hexasmal.csv', ';');
$importer->parseHeader();
$importer->onlyHeader([
	'Code_commune_INSEE',
	'Nom_commune',
	'Code_postal',
	'coordonnees_gps',
]);
$laposte_data = $importer->get();

# Parse La POSTE data
$coordinates = [];
foreach ($laposte_data as $lp) {
	if($code = $lp['Code_commune_INSEE']) {
		# Additionnals zip codes for the same city
		if(isset($coordinates[$code])) {
			if(!in_array($lp['Code_postal'], $coordinates[$code]['ZIP_CODES'])) {
				$coordinates[$code]['ZIP_CODES'][] = $lp['Code_postal'];
			}
		}
		# Add city
		else {
			$coordinates[$code] = [
				'REF_INSEE' => $code,
				'NAME' => $lp['Nom_commune'],
				'GPS' => $lp['coordonnees_gps'],
				'ZIP_CODES' => [
					$lp['Code_postal']
				],
			];
		}
	}
}

echo '"REF_INSEE","NAME","SLUG","NORMALIZED","DEPARTMENT","REGION","IS_DISTRICT","DISTRICT_OF","ZIP_CODES","LATITUDE","LONGITUDE"';
echo '<br>';
foreach ($cities_to_import as $codeInsee => $cityName) {

	# Code department
	$department = substr($codeInsee, 0, 3);

	# Zip codes
	$zipCodes = implode(',', $coordinates[$codeInsee]['ZIP_CODES'] ?? []);

	# Add GPS if exist
	$latitude = '';
	$longitude = '';
	if(isset($coordinates[$codeInsee]) && $coordinates[$codeInsee]['GPS']) {
		$gps = explode(',', $coordinates[$codeInsee]['GPS']);
		$latitude = trim($gps[0] ?? '');
		$longitude = trim($gps[1] ?? '');
	}

	# Slugs
	$slug = $this->Slugify->clean($cityName);
	$normalized = $this->Slugify->clean($cityName, ' ');

	# Add line
	echo '"'. $codeInsee .'","'. $cityName .'","'. $slug .'","'. $normalized .'","'. $department .'",,"0",,"'. $zipCodes .'","'. $latitude .'","'. $longitude .'"';
	echo '<br>';
}