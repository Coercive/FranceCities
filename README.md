# FranceCities
List of France cities with zip codes, department codes, region codes, districts for former cities, GPS latitude longitude coordinates, and INSEE reference code.

## 2022
There are **34,955** cities in France on January 1, 2022, to which are added **45** city districts.

That is a total of **35,000 entries**, with of course several zip codes for some.

2022 data files are available in **2022_datas** directory *(CSV, CSV for MS Excel, SQL, XLSX, YAML)*

## Foreword
The approach comes from the author **Thomas Loiret**, who offers on the following link reliable sources and a way to compile them. [https://b0uh.github.io/how-to-get-the-list-of-french-cities-and-more.html]

## Sources

### LA POSTE - Zip codes and GPS coordinates
Retrieve the CSV file from La Poste for postal codes and GPS coordinates. [https://datanova.legroupe.laposte.fr/explore/dataset/laposte_hexasmal/export/?disjunctive.code_commune_insee&disjunctive.nom_de_la_commune&disjunctive.code_postal&disjunctive.ligne_5]

### INSSEE - List of french cities
Retrieve INSEE Excel data for the list of french cities. Communal division - Table of geographical affiliation of the municipalities. [https://www.insee.fr/fr/information/2028028]

## Programming language used
***
> The following code examples are in PHP.
***

## Convert XLSX
First, the INSEE .xlsx file must be converted. It is necessary to recover the first sheet "COM" (List of municipalities) and the second sheet "ARM" (Municipal districts).

You can use external software to do this, or use phpoffice/phpspreadsheet directly in php in the same script. [https://github.com/PHPOffice/PhpSpreadsheet]

```php
<?php
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Csv;

# INSEE .xlsx filepath
$source = '/table-appartenance-geo-communes.xlsx';

# Sheet 1 : [COM] Communes (List of municipalities)
$insee_com = '/insee_com.csv';

# Load Excel sheet and Transform
$reader = new Xlsx;
$reader->setReadDataOnly(true); # do not convert to integer
$sheet = $reader->load($source);
$writer = new Csv($sheet);
$writer->setDelimiter(';');
$writer->setSheetIndex(0); # select first sheet
$writer->save($insee_com);

# Sheet 2 : [ARM] Arrondissements (Municipal districts)
$insee_arm = '/insee_arm.csv';

# Load Excel sheet and Transform
$reader = new Xlsx;
$reader->setReadDataOnly(true); # do not convert to integer
$sheet = $reader->load($source);
$writer = new Csv($sheet);
$writer->setDelimiter(';');
$writer->setSheetIndex(1); # select second sheet
$writer->save($insee_arm);
```
## (1) READ DATAS

### Read LA POSTE data .csv
Get only the necessary fields, and convert the csv to a php array.

```php
<?php
use Coercive\Utility\Csv\Importer;

# Delimiter ;
# Columns : Code_commune_INSEE;Nom_commune;Code_postal;Ligne_5;Libellé_d_acheminement;coordonnees_gps
$source = '/laposte_hexasmal.csv';

$importer = new Importer($source, ';');
$importer->parseHeader();
$importer->onlyHeader([
  'Code_commune_INSEE',
  'Nom_commune',
  'Code_postal',
  'coordonnees_gps',
]);
$laposte_data = $importer->get();
```

### Read INSEE COM data .csv (sheet 1)
Get only the necessary fields, and convert the csv to a php array.

```php
<?php
use Coercive\Utility\Csv\Importer;

# Sheet 1 : [COM] Communes (List of municipalities)
$insee_com = '/insee_com.csv';

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
```

### Read INSEE ARM data .csv (sheet 2)
Get only the necessary fields, and convert the csv to a php array.

```php
<?php
use Coercive\Utility\Csv\Importer;

# Sheet 2 : [ARM] Arrondissements (Municipal districts)
$insee_arm = '/insee_arm.csv';

$importer = new Importer($insee_arm, ';');
$importer->seek(5); # skip introduction paragraphs
$importer->parseHeader(false);
$importer->onlyHeader([
  'CODGEO',
  'LIBGEO',
  'DEP',
  'REG',
  'COM', # Communes englobantes (surrounding municipalities)
]);
$insee_arm_data = $importer->get();
```

## (2) PARSE DATAS

### Parse INSEE ARM data
Arranges and prepares the data before the merging step.

```php
<?php
# Data from the previous step
$insee_arm_data = $importer->get();

$districts = [];
foreach ($insee_arm_data as $arm) {
  if($code = $arm['CODGEO']) {
    if(isset($districts[$code])) {
      die('District already exist with code : ' . $code);
    }
    $districts[$code] = [
      'REF_INSEE' => $code,
      'NAME' => trim($arm['LIBGEO']),
      'SLUG' => $this->Slugify->clean(trim($arm['LIBGEO'])),
      'NORMALIZED' => $this->Slugify->clean(trim($arm['LIBGEO']), ' '),
      'DEPARTMENT' => $arm['DEP'],
      'REGION' => $arm['REG'],
      'DISTRICT_OF' => $arm['COM'],
    ];
  }
}
```

#### Get Coercive/Slugify utility
More information here [https://github.com/Coercive/Slugify]

### Parse INSEE COM data
Arranges and prepares the data before the merging step.

```php
<?php
# Data from the previous step
$insee_com_data = $importer->get();

$towns = [];
foreach ($insee_com_data as $com) {
  if($code = $com['CODGEO']) {
    if(isset($towns[$code])) {
      die('Town already exist with code : ' . $code);
    }
    $towns[$code] = [
      'REF_INSEE' => $code,
      'NAME' => trim($com['LIBGEO']),
      'SLUG' => $this->Slugify->clean(trim($com['LIBGEO'])),
      'NORMALIZED' => $this->Slugify->clean(trim($com['LIBGEO']), ' '),
      'DEPARTMENT' => $com['DEP'],
      'REGION' => $com['REG'],
      'DISTRICT_OF' => '',
    ];
  }
}
```

#### Get Coercive/Slugify utility
More information here [https://github.com/Coercive/Slugify]

### Parse La POSTE data
Arranges and prepares the data before the merging step.

```php
<?php
# Data from the previous step
$laposte_data = $importer->get();

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
```

## (3) MERGE & SAVE DATAS
At this stage, we merge data from cities and city districts.

We complete the data with zip codes and GPS coordinates.

Then, if the city is a former city, we assign it the department code with an empty suffix (000) for its zip code, as well as the GPS coordinates of the main district (the first).

```php
<?php
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

  # Add GPS if exist
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
  ]);
}
```

## (4) MANUAL WORK

2022 : 3 cities have no GPS data. We have to do manual search and add the data.

I took a position close to the churches for reference.

- 55000 Culey : 48.755322, 5.266420
- 76420 Bihorel : 49.455277, 1.116896
- 76780 Saint-Lucien : 49.508528, 1.449226
