# FranceCities - Overseas collectivities
List of France overseas collectivities with zip codes, department codes, GPS latitude longitude coordinates, and INSEE reference code.

## 2022
There are **94** french overseas collectivities on March 1, 2017.

2022 data files are available in **overseas_collectivities** directory *(CSV, CSV for MS Excel, SQL, XLSX, YAML)*

## Sources

### INSSEE - List of overseas collectivities
Retrieve manually municipalities lists from here [https://www.insee.fr/fr/information/2028040]

## Programming language used
***
> The following code examples are in PHP.
***

## (1) Set cities to import as a php array
Made manually from the INSEE page source
```
<?php
$cities_to_import = [
	'98711' =>	'Anaa',
	'98712' =>	'Arue',
	'98713' =>	'Arutua',
	'98714' =>	'Bora-Bora',
	# ...
];
```

## (2) Read LA POSTE data .csv
Get only the necessary fields, and convert the csv to a php array.

See example from main README.md

## (3) Prepare data
Here the datas are prepared in CSV format.

But the export is actually a simple 'echo' for ease of understanding.

```
<?php
echo '"REF_INSEE","NAME","SLUG","NORMALIZED","DEPARTMENT","REGION","IS_DISTRICT","DISTRICT_OF","ZIP_CODES","LATITUDE","LONGITUDE"';
foreach ($cities_to_import as $codeInsee => $cityName) {

	# Code department : take de first three chars
	$department = substr($codeInsee, 0, 3);

	# Zip codes (see main readme)
	$zipCodes = implode(',', $coordinates[$codeInsee]['ZIP_CODES'] ?? []);

	# Add GPS if exist (see main readme)
	$latitude = '';
	$longitude = '';
	if(isset($coordinates[$codeInsee]) && $coordinates[$codeInsee]['GPS']) {
		$gps = explode(',', $coordinates[$codeInsee]['GPS']);
		$latitude = trim($gps[0] ?? '');
		$longitude = trim($gps[1] ?? '');
	}

	# Slugs (see main readme) (Get the class : https://github.com/Coercive/Slugify)
	$slug = $this->Slugify->clean($cityName);
	$normalized = $this->Slugify->clean($cityName, ' ');

	# Add data line
	echo '<br>';
	echo '"'. $codeInsee .'","'. $cityName .'","'. $slug .'","'. $normalized .'","'. $department .'",,"0",,"'. $zipCodes .'","'. $latitude .'","'. $longitude .'"';
}
```

## (4) Manually complete missing coordinates

**Polynésie française**

- Anaa 98786,98790,98760 : -17.410531, -145.492830
- Arue 98701 : -17.531545, -149.528851
- Arutua 98761,98762,98785 : -15.293538, -146.785928
- Bora-Bora 98730 : -16.498962, -151.736639
- Faaa 98704 : -17.563093, -149.596811
- Fakarava 98790,98787,98763,98764 : -16.301956, -145.626189
- Fangatau 98765,98766 : -15.821478, -140.888364
- Fatu-Hiva 98740 : -10.487820, -138.647943
- Gambier 98755,98792,98793 : -23.121970, -134.974315
- Hao 98790,98767 : -18.113403, -140.904359
- Hikueru 98790,98768 : -17.550378, -142.610521
- Hitiaa O Te Ra 98705,98706,98708,98707 : -17.561262, -149.368478
- Hiva-Oa 98741,98749,98796 : -17.547756, -149.366204
- Huahine 98731,98732 : -16.784023, -150.986709
- Mahina 98709,98710 : -17.509741, -149.480726
- Makemo 98790,98769,98789 : -16.606753, -143.701645
- Manihi 98771,98770 : -14.464131, -146.054349
- Maupiti 98732 : -16.444034, -152.259157
- Moorea-Maiao 98728,98729 : -17.547376, -149.821121
- Napuka 98772 : -14.170352, -141.231715
- Nuku-Hiva 98742,98796,98748 : -8.862928, -140.141781
- Nukutavake 98773,98788 : -19.280843, -138.783709
- Paea 98711 : -17.690768, -149.582963
- Papara 98712 : -17.760839, -149.503515
- Papeete 98714 : -17.533005, -149.567520
- Pirae 98716 : -17.535537, -149.547310
- Pukapuka 98774 : -14.811570, -138.839127
- Punaauia 98703,98718 : -17.594023, -149.612080
- Raivavae 98750 : -23.869694, -147.687239
- Rangiroa 98775,98776,98790,98777,98778 : -14.942337, -147.703164
- Rapa 98751,98794 : -27.627045, -144.337009
- Reao 98780,98779 : -18.466728, -136.463115
- Rimatara 98752,98795 : -22.649157, -152.805149
- Rurutu 98753 : -22.466437, -151.343346
- Tahaa 98733,98734 : -16.612354, -151.495729
- Tahuata 98743 : -9.938333, -139.085260
- Taiarapu-Est 98719,98720,98721,98722 : -17.733057, -149.306752
- Taiarapu-Ouest 98724,98723,98725 : -17.811245, -149.288740
- Takaroa 98781,98790,98782 : -14.456888, -145.028072
- Taputapuatea 98735 : -16.835938, -151.358598
- Tatakoto 98783 : -17.345460, -138.451821
- Teva I Uta 98726,98727 : -17.764363, -149.401823
- Tubuai 98754 : -23.381415, -149.490145
- Tumaraa 98735 : -16.787674, -151.479752
- Tureia 98784 : -20.771083, -138.564291
- Ua-Huka 98747,98744 : -8.914607, -139.533869
- Ua-Pou 98745,98746 : -9.382538, -140.058951
- Uturoa 98735 : -16.725701, -151.457907

**Nouvelle-Calédonie**

- Belep 98811 : -19.750122, 163.666335
- Bouloupari 98812 : -21.862633, 166.050904
- Bourail 98870 : -21.561670, 165.491996
- Canala 98813 : -21.520295, 165.958893
- Dumbéa 98830,98836,98837,98839,98835 : -22.158216, 166.453514
- Farino 98881 : -21.665824, 165.775731
- Hienghène 98815 : -20.688630, 164.943881
- Houaïlou 98816,98838 : -21.278387, 165.630087
- Île-des-Pins 98832 : -22.615612, 167.480983
- Kaala-Gomen 98817 : -20.666537, 164.398277
- Koné 98859,98860 : -21.056387, 164.857063
- Koumac 98850 : -20.557453, 164.285495
- La Foa 98880 : -21.708432, 165.826006
- Lifou 98820,98885,98884 : -20.954744, 167.221178
- Maré 98828,98878 : -21.489473, 168.000064
- Moindou 98819 : -21.692723, 165.677347
- Mont-Dore 98874,98876,98810,98809,98875 : -22.266108, 166.566186
- Nouméa 98800 : -22.268168, 166.457038
- Ouégoa 98821 : -20.344466, 164.438317
- Ouvéa 98814 : -20.644496, 166.569525
- Païta 98840,98889,98890 : -22.133804, 166.360606
- Poindimié 98822 : -20.937112, 165.332115
- Ponérihouen 98823 : -21.071245, 165.402002
- Pouébo 98824 : -20.391215, 164.576011
- Pouembout 98825 : -21.115408, 164.899464
- Poum 98826 : -20.231607, 164.024066
- Poya 98827,98877 : -21.344747, 165.157530
- Sarraméa 98882 : -21.642163, 165.853665
- Thio 98829 : -21.610504, 166.215669
- Touho 98831 : -20.790733, 165.258342
- Voh 98883,98833 : -20.948963, 164.685938
- Yaté 98834 : -22.165755, 166.950065
- Kouaoua 98818 : -21.396270, 165.828710