<?php
namespace keeko\tools\command;

use keeko\tools\command\AbstractGenerateCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MagicCommand extends AbstractGenerateCommand {
	
	protected function configure() {
		$this
			->setName('magic')
			->setDescription('Magically does everything')
		;
	
		parent::configure();
	}
	
	public function execute(InputInterface $input, OutputInterface $output) {
		// pre check
		$package = $this->getPackage();
		if (!isset($package['type']) && !isset($package['name'])) {
			throw new \DomainException('No type and name found in composer.json - please run `keeko init`.');
		}
		
		if ($package['type'] === 'keeko-module') {
			$module = $this->getKeekoModule();
			if (count($module) === 0) {
				throw new \DomainException('No module definition found in composer.json - please run `keeko init`.');
			}
		}
		
		// let's go		
		$output->writeln(sprintf('<fg=magenta>%s</fg=magenta>', $this->getRandomHex()));
		$output->writeln('<fg=cyan>And then a miracle appears ...</fg=cyan>');
		
		$args = [];
		if (($schema = $input->getOption('schema')) !== null) {
			$args['--schema'] = $schema;
		}
		
		if (($composer = $input->getOption('composer')) !== null) {
			$args['--composer'] = $composer;
		}
		
		$input = new ArrayInput($args);
		$input->setInteractive(false);
		
		if ($package['type'] === 'keeko-module') {
			$this->runCommand('generate:action', $input, $output);
			$this->runCommand('generate:response', $input, $output);
			$this->runCommand('generate:api', $input, $output);
		}
	}

	protected function getRandomHex() {
		return $this->hex[array_rand($this->hex)];
	}
	
	private $hex = [
		'Ene mene Schmieg, Kartoffelbrei, los, flieg,... Hex-hex! ',
		'Ene mene mitzel, ein riesengroßes Schnitzel... Hex-hex!',
		'Blitz und Donner kommt herbei, Ene mene eins, zwei, drei... Hex-hex!',
		'Ene mene muh herbei komme die Kuh... Hex-hex!',
		'Ene mene britzel, fertig sei das Schnitzel, Ene mene morimad und auch der Kartoffelsalat... Hex-hex!',
		'Ene mene Hühnerdreck her mit etwas Salzgebäck... Hex-hex!',
		'Ene mene Keks und Wein sollen gut und lecker sein... Hex-hex!',
		'Ene mene melt, fertig sei das Zelt; und ein Gitter drumherum fidibei und fidibum; auch noch ein Moskitonetz und jetzt mache ich:... Hex-hex!',
		'Ene mene Moos, wir brauchen schnell ein Floß; mit ner hohen Reling dran für fünf Mann... Hex-hex!',
		'Ene mene nicht geklaut im Wagen ist das Sauerkraut... Hex-hex!',
		'Ene mene Papitrost guter Wein im Glas und Prost... Hex-hex!',
		'Ene mene rundes Fässchen hier steht jetzt ein leckeres Fresschen Ene mene Warzenschwein auch ein Fläschchen roter Wein... Hex-hex!',
		'Ene mene Schelm, wir brauchen einen Sturzhelm... Hex-hex!',
		'Ene mene spaten, es wartet Schweinebraten... Hex-hex!',
		'Ene mene steg hier ist jetzt ein Radfahrweg, Ene mene mauf und hier auch, Ene mene rüben auch dort drüben, Ene mene mende auch am anderen Ende... Hex-hex!',
		'Ene mene Urgestank her mit einem kleinen Trank... Hex-hex!',
		'Ene mene Windesbraut, Eisbein her mit Sauerkraut. Ene mene schnudding, und Vanillepudding... Hex-hex!',
		'Ene mene Baldrian flieg zum Hochhaus nebenan... Hex-hex!',
		'Ene mene Baldrian, Baldrian schieb Wolke an... Hex-hex!',
		'Ene mene huckebein Baldrian bringt Poli heim... Hex-hex!',
		'Ene mene Zappelhahn, fliege los, O Baldrian... Hex-hex!',
		'Ene mene Brandung Kartoffelbrei los Landung... Hex-hex!',
		'Ene mene Drüfte Kartoffelbrei flieg durch die Lüfte... Hex-hex!',
		'Ene mene einerlei zum Hochhaus flieg Kartoffelbrei... Hex-hex!',
		'Ene mene Mausebrei Kartoffelbrei mein Flugzeug sei... Hex-hex!',
		'Ene mene nur Verdruß Kartoffelbrei du machst jetzt Schluß',
		'Ene mene Mei, Kartoffelbrei mein Flugzeug sei,... Hex-hex!',
		'Ene mene Mei, flieg los Kartoffelbrei... Hex-hex! ',
		'Ene mene El Ole, El Flitzo geh\' kurz in die Höh\'.',
		'Ene mene El Ole, El Flitzo gehe in die Höh\'',
		'Ene mene Grande, El Flitzo lande.',
		'Ene mene mank, jetzt kommt der dritte Gang... Hex-hex!',
		'Enemene Adebar, zeig\' mir, wie das damals war... Hex-hex!',
		'Aberakadabara mungo dschungo wabera urowaldo liani tarzi warzi schikani mungo dschungo wabera senzo kandelabera aberakadabara.',
		'Am dam dei, keine Schwimmhaut an Boris Finger sei... Hex-hex!',
		'Am dam deibeldei, ein Frosch mein Bruder Boris sei... Hex-hex!',
		'Dschungo mungo agakan, Minihex nicht gut getan. Blitz und Donner, 1, 2, 3; Ene mene Fliegenei, Kokosnuss und Affenschwanz, meine Tochter bleibe ganz aus dem Dschungel, sei sie hier und mit ihr die anderen vier... Hex-hex! Doppel-... Hex-hex!',
		'Ene mene nasse Sachen, Wind der soll sie trocken machen... Hex-hex!',
		'Ene mene Eiersuchen, wieder lecker sei der Kuchen; enemene Jugendzeit, trocken sei Amandas Kleid; enemene Tintenschreibe, heil sei auch die Fensterscheibe; enemene schwarze Asche, weg sei in Karlas Strumpf die Masche... Hex-hex!',
		'Ene mene dicke Masche sei ein Brief und nicht mehr Asche... Hex-hex!',
		'Ene mene Eselsohr Einkaufswagen wie zu vor... Hex-hex!',
		'Ene mene großer Kinderlauf, alle Türen gehen schnell wieder auf... Hex-hex!',
		'Ene mene Kuh Achim sein sollst du... Hex-hex!',
		'Ene mene meck, grün sei wieder weg... Hex-hex!',
		'Ene mene mei Keks werd Kartoffelbrei... Hex-hex!',
		'Ene mene mün weg sei das Grün... Hex-hex!',
		'Ene mene nebenbei fort der lange Rüssel sei... Hex-hex!',
		'Ene mene nur Verdruß Kartoffelbrei du machst jetzt Schluß... Hex-hex!',
		'Ene mene Salz im Meer Einkaufswagen wieder leer... Hex-hex!',
		'Ene mene steg, weg sei der Radfahrweg, Ene mene säge, weg alle Radfahrwege... Hex-hex!',
		'Ene mene Stäbchenfisch lande wieder auf dem Tisch... Hex-hex!',
		'Ene mene Wasserfall, Kinder seit jetzt ganz normal... Hex-hex!',
		'Ene mene Elefant Brief hierher in meine Hand... Hex-hex!',
		'Ene mene Fliegenbein, Müllers Schweinebraten bei mir sei... Hex-hex!',
		'Ene mene gauckeley, die Wolke schiebt Kartoffelbrei... Hex-hex!',
		'Ene mene großer Kinderlauf, alle Türen gehen schnell wieder auf... Hex-hex!',
		'Ene mene mall, wir fliegen über den Wasserfall... Hex-hex!',
		'Ene mene Maus und Klaus, Hexbuch aus Hexenlabor heraus... Hex-hex!',
		'Ene mene mext, Schuhe seit verhext. Fliegt hoch jetzt zehn Meter, runter kommt ihr später... Hex-hex!',
		'Ene mene Mut fliegen kann der Hut... Hex-hex!',
		'Ene mene Stäbchenfisch lande wieder auf dem Tisch... Hex-hex!',
		'Ene mene test Boris der klebt fest... Hex-hex!',
		'Ene mene Fliegenbein, Müllers Schweinebraten bei mir sei... Hex-hex!',
		'Ene mene großes Dino-Ei, Oma Grete ist schnell wieder herbei... Hex-hex!',
		'Ene mene butter grün sei meine Mutter... Hex-hex!',
		'Ene mene dicke Masche sei ein Brief und nicht mehr Asche... Hex-hex!',
		'Ene mene matze kuh sei eine Katze... Hex-hex!',
		'Ene mene mecks, Besen sei ein Keks... Hex-hex!',
		'Ene mene mei Keks werd Kartoffelbrei... Hex-hex!',
		'Ene mene mig, bumkrach sei Musik... Hex-hex!',
		'Ene mene muh Achim sei ne Kuh... Hex-hex!',
		'Ene mene nebenbei fort der lange Rüssel sei... Hex-hex!',
		'Ene mene Satteltasche blauer Brief werde zu Asche... Hex-hex!',
		'Ene mene schich, lieber Hund komm und sprich... Hex-hex!',
		'Ene mene Schnüffel Nase sei ein Rüssel... Hex-hex!',
		'Ene mene eiderdauß, Dicker werde eine Laus',
		'Ene mene meiss Dose werde heiss... Hex-hex!',
		'Ene mene Pferdemief öffne dich du blauer Brief... Hex-hex!',
		'Ene mene max an der Nase er sich kratzt... Hex-hex!',
		'Ene mene Pferdewagen Papi stelle andere Fragen... Hex-hex!',
		'Ene mene Riesenzorn, sei verraucht beim Nasenhorn... Hex-hex!',
		'Ene mene superbrav, fallt alle jetzt in tiefen Schlaf; morgen früh seid wieder wach, Ene mene Rieselbach... Hex-hex!',
		'Ene mene Warzenschwein Flori lädt Marita ein... Hex-hex!',
		'Ene mene Wasserfall, Kinder seit jetzt ganz normal... Hex-hex!',
		'Ene mene weil ich es will, sind die Kinder jetzt mucksmäuschenstill... Hex-hex!',
		'Ene mene Tiefkühlschrank, fertig ist der Zaubertrank... Hex-hex!',
	];
}