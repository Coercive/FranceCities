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
 * @copyright 2024 Anthony Moral
 * @license MIT
 */

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

# Select from 2022 data
$overseas = $this->DB->select('OVERSEAS_COLLECTIVITIES_COERCIVE', [
	'REF_INSEE',
	'NAME',
	'SLUG',
	'NORMALIZED',
	'DEPARTMENT',
	'REGION',
	'IS_DISTRICT',
	'DISTRICT_OF',
	'ZIP_CODES',
	'LATITUDE',
	'LONGITUDE',
]);

foreach ($overseas as $oversea) {

	$coordinate = $coordinates[$oversea['REF_INSEE']] ?? [];
	$geometry = $coordinate['GEOMETRY_GPS'] ?? '';

	# Save the data (use your database object, or save in csv, or whatever else you want...)
	$entry = [
		'UNIQ_REF_INSEE' => 'COM' . $oversea['REF_INSEE'],
		'REF_INSEE' => $oversea['REF_INSEE'],
		'NORMALIZED_NAME' => strtoupper($Slugify->clean($oversea['NAME'], ' ')),
		'NAME' => $oversea['NAME'],
		'FULLNAME' => $oversea['NAME'],
		'SEARCHFULLTEXT' => $Slugify->searchSqlCleaner($oversea['NAME']),
		'SLUG' => $oversea['SLUG'],
		'NORMALIZED' => $oversea['NORMALIZED'],
		'DEPARTMENT' => $oversea['DEPARTMENT'],
		'REGION' => $oversea['REGION'],
		'IS_DISTRICT' => 0,
		'DISTRICT_OF' => '',
		'ASSOCIATED_MUNICIPALITY' => 0,
		'DELEGATED_MUNICIPALITY' => 0,
		'OVERSEA_MUNICIPALITY' => 1,
		'ZIP_CODES' => $oversea['ZIP_CODES'],
		'LATITUDE' => $oversea['LATITUDE'],
		'LONGITUDE' => $oversea['LONGITUDE'],
		'GEOMETRY_GPS' => $geometry,
	];
}