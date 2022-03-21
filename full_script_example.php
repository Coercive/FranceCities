<?php
namespace Coercive\FranceCities;

use Coercive\Utility\Csv\Importer;
use Coercive\Utility\Slugify\Slugify;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Csv;

# LA POSTE CSV FILE
$laposte_hexasmal = '/cities/laposte_hexasmal.csv';

# INSEE XLSX FILE
$insee_xlsx = '/cities/table-appartenance-geo-communes.xlsx';

# Sheet 1 : [COM] Communes (List of municipalities)
$insee_com = '/insee_com.csv';

# Load Excel sheet and Transform
$reader = new Xlsx;
$reader->setReadDataOnly(true); # do not convert to integer
$sheet = $reader->load($insee_xlsx);
$writer = new Csv($sheet);
$writer->setDelimiter(';');
$writer->setSheetIndex(0); # select first sheet
$writer->save($insee_com);

# Sheet 2 : [ARM] Arrondissements (Municipal districts)
$insee_arm = '/insee_arm.csv';

# Load Excel sheet and Transform
$reader = new Xlsx;
$reader->setReadDataOnly(true); # do not convert to integer
$sheet = $reader->load($insee_xlsx);
$writer = new Csv($sheet);
$writer->setDelimiter(';');
$writer->setSheetIndex(1); # select second sheet
$writer->save($insee_arm);

# Read LA POSTE data .csv
$importer = new Importer($laposte_hexasmal, ';');
$importer->parseHeader();
$importer->onlyHeader([
	'Code_commune_INSEE',
	'Nom_commune',
	'Code_postal',
	'coordonnees_gps',
]);
$laposte_data = $importer->get();

# Read INSEE COM data .csv (sheet 1)
$importer = new Importer($insee_com, ';');
$importer->seek(5); # skip introduction paragraphs
$importer->parseHeader(false);
$importer->onlyHeader([
	'CODGEO',
	'LIBGEO',
	'DEP',
	'REG',
]);
$insee_com_data = $importer->get();

# Read INSEE ARM data .csv (sheet 2)
$importer = new Importer($insee_arm, ';');
$importer->seek(5); # skip introduction paragraphs
$importer->parseHeader(false);
$importer->onlyHeader([
	'CODGEO',
	'LIBGEO',
	'DEP',
	'REG',
	'COM', # Surrounding municipalities
]);
$insee_arm_data = $importer->get();

/**
 * Load Slugify
 *
 * @link https://github.com/Coercive/Slugify
 */
$Slugify = new Slugify;

# Parse INSEE ARM data
$districts = [];
foreach ($insee_arm_data as $arm) {
	if($code = $arm['CODGEO']) {
		if(isset($districts[$code])) {
			die('District already exist with code : ' . $code);
		}
		$districts[$code] = [
			'REF_INSEE' => $code,
			'NAME' => trim($arm['LIBGEO']),
			'SLUG' => $Slugify->clean(trim($arm['LIBGEO'])),
			'NORMALIZED' => $Slugify->clean(trim($arm['LIBGEO']), ' '),
			'DEPARTMENT' => $arm['DEP'],
			'REGION' => $arm['REG'],
			'DISTRICT_OF' => $arm['COM'],
		];
	}
}

# Parse INSEE COM data
$towns = [];
foreach ($insee_com_data as $com) {
	if($code = $com['CODGEO']) {
		if(isset($towns[$code])) {
			die('Town already exist with code : ' . $code);
		}
		$towns[$code] = [
			'REF_INSEE' => $code,
			'NAME' => trim($com['LIBGEO']),
			'SLUG' => $Slugify->clean(trim($com['LIBGEO'])),
			'NORMALIZED' => $Slugify->clean(trim($com['LIBGEO']), ' '),
			'DEPARTMENT' => $com['DEP'],
			'REGION' => $com['REG'],
			'DISTRICT_OF' => '',
		];
	}
}

# Parse La POSTE data
$coordinates = [];
foreach ($laposte_data as $lp) {
	if($code = $lp['Code_commune_INSEE']) {
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
				'NAME' => $lp['Nom_commune'],
				'GPS' => $lp['coordonnees_gps'],
				'ZIP_CODES' => [
					$lp['Code_postal']
				],
			];
		}
	}
}

# MERGE & SAVE DATAS
foreach (array_merge($towns, $districts) as $town) {

	# If it's a district of Lyon, Paris or Marseille, we still create a global entry for the city
	# with as GPS position from the first main district data.
	$isMapped = false;
	$mappedCode = $town['REF_INSEE'];
	if(!array_key_exists($mappedCode, $coordinates)) {
		foreach ($districts as $district) {
			if($mappedCode === $district['DISTRICT_OF']) {
				$isFirst = (bool) preg_match('`^\d+1$`', $district['REF_INSEE']);
				if($isFirst) {
					$isMapped = true;
					$mappedCode = $district['REF_INSEE'];
				}
			}
		}
	}

	# Zip codes
	# If former cities : use main departement zip extends with empty (000) suffix
	$zipCodes = implode(',', $coordinates[$mappedCode]['ZIP_CODES'] ?? []);
	if($isMapped) {
		$zipCodes = $town['DEPARTMENT'] . '000';
	}

	# Aff GPS if exist
	$latitude = '';
	$longitude = '';
	if(isset($coordinates[$mappedCode]) && $coordinates[$mappedCode]['GPS']) {
		$gps = explode(',', $coordinates[$mappedCode]['GPS']);
		$latitude = trim($gps[0] ?? '');
		$longitude = trim($gps[1] ?? '');
	}

	# Save the data (use your database object, or save in csv, or whatever else you want...)
	$entry = [
		'REF_INSEE' => $town['REF_INSEE'],
		'NAME' => $town['NAME'],
		'SLUG' => $town['SLUG'],
		'NORMALIZED' => $town['NORMALIZED'],
		'DEPARTMENT' => $town['DEPARTMENT'],
		'REGION' => $town['REGION'],
		'IS_DISTRICT' => $town['DISTRICT_OF'] ? '1' : '0',
		'DISTRICT_OF' => $town['DISTRICT_OF'],
		'ZIP_CODES' => $zipCodes,
		'LATITUDE' => $latitude,
		'LONGITUDE' => $longitude,
	];
}

# ADD SOME GPS MANUALY

# - 55000 Culey : 48.755322, 5.266420
# - 76420 Bihorel : 49.455277, 1.116896
# - 76780 Saint-Lucien : 49.508528, 1.449226

die('OK ' . time());