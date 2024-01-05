# French Overseas Collectivities - Process Explanation
See the main readme for global information.

## (1) ReOpen data from La Poste for compte GPS info
Same as 2023 france municipalities
```php
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
```

Prepare data, same as 2023 france municipalities
```php
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
```

## (2) Load oversea collectivites from 2022
Get only the necessary fields, and convert the csv to a php array.
```php
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
```

## (3) Prepare data with same fields as france municipalities for merge
Use coordinates from la_poste to complete geometry GPS

```php
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
```