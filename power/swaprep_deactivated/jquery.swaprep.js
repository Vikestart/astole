$('a:not([href="#"]):not([target="_blank"])').click(function() {
	$('#page_loading').fadeIn('fast');
});
$(window).on('load', function() {
	$(document).ready(function($) {
		$('#page_loading').fadeOut('fast');
		var myObj, i, j, x = "";
		myObj = {
			"brands": [
				{
					"brand":"Acer",
					"routines": [
						{"condition":"Tilbehør til under 1000 kr", "resolution":"Byttes over disk inntil 2 år etter salgsdato."},
						{"condition":"Over 1000 kr", "resolution":"Sendes til verksted for reparasjon."}
					]
				},
				{
					"brand":"AEG",
					"routines": [
						{"condition":"Hvitevarer", "resolution":"Hvitevarer skal repareres av verksted. Hjemmeservice kan være tilgjengelig for store produkter."},
						{"condition":"Annet under 1500 kr", "resolution":"Byttes over disk inntil 2 år etter salgsdato."},
						{"condition":"Annet over 1500 kr", "resolution":"Butikken tar inn produktet og søker kredit/ombytte. Byttes med kunde senere hvis kravet godkjennes."}
					]
				},
				{
					"brand":"AKG",
					"routines": [
						{"condition":"Under 1000 kr	", "resolution":"Byttes over disk inntil 2 år etter salgsdato."},
						{"condition":"Over 1000 kr", "resolution":"Sendes til verksted for reparasjon."}
					]
				},
				{
					"brand":"Akai",
					"routines": [
						{"condition":"", "resolution":"Byttes alltid over disk inntil 2 år etter salgsdato."}
					]
				},
				{
					"brand":"Amadeus",
					"routines": [
						{"condition":"", "resolution":"Byttes alltid over disk inntil 2 år etter salgsdato."}
					]
				},
				{
					"brand":"Amplifi",
					"routines": [
						{"condition":"Under 1000 kr", "resolution":"Byttes over disk inntil 2 år etter salgsdato."},
						{"condition":"Over 1000 kr", "resolution":"Butikken tar inn produktet og søker kredit/ombytte. Byttes med kunde senere hvis kravet godkjennes."}
					]
				},
				{
					"brand":"Anki",
					"routines": [
						{"condition":"", "resolution":"Byttes alltid over disk inntil 2 år etter salgsdato."}
					]
				},
				{
					"brand":"Apple",
					"routines": [
						{"condition":"Beats by Dr. Dre", "resolution":"Byttes over disk inntil 2 år etter salgsdato på garanti. Forsikringssaker skal sendes inn til Conmodo."},
						{"condition":"Alt annet", "resolution":"Alt sendes til verksted for reparasjon, inkludert tilbehør fra salgspakken."}
					]
				},
				{
					"brand":"ASKO",
					"routines": [
						{"condition":"", "resolution":"Skal repareres av verksted. Hjemmeservice kan være tilgjengelig for store produkter."}
					]
				},
				{
					"brand":"ASUS",
					"routines": [
						{"condition":"Tilbehør til under 1000 kr", "resolution":"Byttes over disk inntil 2 år etter salgsdato."},
						{"condition":"Mobiltelefon og nettbrett", "resolution":"Alt sendes til verksted for reparasjon, inkludert tilbehør fra salgspakken."},
						{"condition":"Alt annet", "resolution":"Sendes til verksted for reparasjon."}
					]
				},
				{
					"brand":"BaByliss",
					"routines": [
						{"condition":"", "resolution":"Byttes alltid over disk inntil 2 år etter salgsdato."}
					]
				},
				{
					"brand":"Bang & Olufsen",
					"routines": [
						{"condition":"Under 1500 kr", "resolution":"Byttes over disk inntil 2 år etter salgsdato."},
						{"condition":"Over 1500 kr", "resolution":"Butikken tar inn produktet og søker kredit/ombytte. Byttes med kunde senere hvis kravet godkjennes."}
					]
				},
				{
					"brand":"Beats by Dr. Dre",
					"routines": [
						{"condition":"Garanti", "resolution":"Byttes over disk inntil 2 år etter salgsdato."},
						{"condition":"Forsikring", "resolution":"Sendes til verksted for kontroll."}
					]
				},
				{
					"brand":"BEHA",
					"routines": [
						{"condition":"Under 1000 kr", "resolution":"Byttes over disk inntil 2 år etter salgsdato."},
						{"condition":"Over 1000 kr", "resolution":"Skal repareres av verksted. Hjemmeservice kan være tilgjengelig for store produkter."}
					]
				},
				{
					"brand":"Bosch",
					"routines": [
						{"condition":"Under 1000 kr", "resolution":"Byttes over disk inntil 2 år etter salgsdato."},
						{"condition":"Over 1000 kr", "resolution":"Skal repareres av verksted. Hjemmeservice kan være tilgjengelig for store produkter."}
					]
				},
				{
					"brand":"Bose",
					"routines": [
						{"condition":"", "resolution":"Alt sendes til verksted for reparasjon, inkludert tilbehør fra salgspakken."}
					]
				},
				{
					"brand":"Braun",
					"routines": [
						{"condition":"Under 1000 kr", "resolution":"Byttes over disk inntil 2 år etter salgsdato."},
						{"condition":"Over 1000 kr", "resolution":"Sendes til verksted for reparasjon."}
					]
				},
				{
					"brand":"C-Frame",
					"routines": [
						{"condition":"Under 1000 kr", "resolution":"Byttes over disk inntil 2 år etter salgsdato."},
						{"condition":"Over 1000 kr", "resolution":"Sendes til verksted for reparasjon."}
					]
				},
				{
					"brand":"Candy",
					"routines": [
						{"condition":"", "resolution":"Skal repareres av verksted. Hjemmeservice kan være tilgjengelig for store produkter."}
					]
				},
				{
					"brand":"Canon",
					"routines": [
						{"condition":"Tilbehør til under 1000 kr", "resolution":"Byttes over disk inntil 2 år etter salgsdato."},
						{"condition":"Printere til under 1500 kr", "resolution":"Byttes over disk inntil 2 år etter salgsdato."},
						{"condition":"Alt annet", "resolution":"Sendes til verksted for reparasjon."}
					]
				},
				{
					"brand":"Casio",
					"routines": [
						{"condition":"", "resolution":"Byttes alltid over disk inntil 2 år etter salgsdato."}
					]
				},
				{
					"brand":"CAT",
					"routines": [
						{"condition":"", "resolution":"Alt sendes til verksted for reparasjon, inkludert tilbehør fra salgspakken."}
					]
				},
				{
					"brand":"CLSF",
					"routines": [
						{"condition":"", "resolution":"Byttes alltid over disk inntil 2 år etter salgsdato."}
					]
				},
				{
					"brand":"Como Audio",
					"routines": [
						{"condition":"Under 1500 kr", "resolution":"Byttes over disk inntil 2 år etter salgsdato."},
						{"condition":"Over 1500 kr", "resolution":"Butikken tar inn produktet og søker kredit/ombytte. Byttes med kunde senere hvis kravet godkjennes."}
					]
				},
				{
					"brand":"Dacota",
					"routines": [
						{"condition":"", "resolution":"Byttes alltid over disk inntil 2 år etter salgsdato."}
					]
				},
				{
					"brand":"Dantax",
					"routines": [
						{"condition":"", "resolution":"Byttes alltid over disk inntil 2 år etter salgsdato."}
					]
				},
				{
					"brand":"dbramante1928",
					"routines": [
						{"condition":"", "resolution":"Byttes alltid over disk inntil 2 år etter salgsdato."}
					]
				},
				{
					"brand":"DBS",
					"routines": [
						{"condition":"", "resolution":"Alt sendes til verksted for reparasjon, inkludert tilbehør fra salgspakken."}
					]
				},
				{
					"brand":"DJI",
					"routines": [
						{"condition":"", "resolution":"Alt sendes til verksted for reparasjon, inkludert tilbehør fra salgspakken."}
					]
				},
				{
					"brand":"DeLonghi",
					"routines": [
						{"condition":"Under 1000 kr", "resolution":"Byttes over disk inntil 2 år etter salgsdato."},
						{"condition":"Over 1000 kr", "resolution":"Sendes til verksted for reparasjon."}
					]
				},
				{
					"brand":"Dolce Gusto",
					"routines": [
						{"condition":"Under 1500 kr", "resolution":"Byttes over disk inntil 2 år etter salgsdato."},
						{"condition":"Over 1500 kr", "resolution":"Sendes til verksted for reparasjon."}
					]
				},
				{
					"brand":"Doro",
					"routines": [
						{"condition":"Under 2500 kr", "resolution":"Byttes over disk inntil 2 år etter salgsdato."},
						{"condition":"Over 2500 kr", "resolution":"Butikken tar inn produktet og søker kredit/ombytte. Reklamasjonsfrist på 5 år. Byttes med kunde senere hvis kravet godkjennes."}
					]
				},
				{
					"brand":"Dyson",
					"routines": [
						{"condition":"", "resolution":"Alt sendes til verksted for reparasjon, inkludert tilbehør fra salgspakken."}
					]
				},
				{
					"brand":"Eico",
					"routines": [
						{"condition":"", "resolution":"Alt sendes til verksted for reparasjon, inkludert tilbehør fra salgspakken."}
					]
				},
				{
					"brand":"Electrolux",
					"routines": [
						{"condition":"Under 1500 kr", "resolution":"Byttes over disk inntil 2 år etter salgsdato."},
						{"condition":"Over 1500 kr", "resolution":"Skal repareres av verksted. Hjemmeservice kan være tilgjengelig for store produkter."}
					]
				},
				{
					"brand":"Eletra",
					"routines": [
						{"condition":"", "resolution":"Byttes alltid over disk inntil 2 år etter salgsdato."}
					]
				},
				{
					"brand":"Epson",
					"routines": [
						{"condition":"Tilbehør til under 1000 kr", "resolution":"Byttes over disk inntil 2 år etter salgsdato."},
						{"condition":"Printere til under 1500 kr", "resolution":"Byttes over disk inntil 2 år etter salgsdato."},
						{"condition":"Alt annet", "resolution":"Sendes til verksted for reparasjon."}
					]
				},
				{
					"brand":"Ferrelli",
					"routines": [
						{"condition":"", "resolution":"Byttes alltid over disk inntil 2 år etter salgsdato."}
					]
				},
				{
					"brand":"Finlux",
					"routines": [
						{"condition":"", "resolution":"Alt sendes til verksted for reparasjon, inkludert tilbehør fra salgspakken."}
					]
				},
				{
					"brand":"Fitbit",
					"routines": [
						{"condition":"Charge 2", "resolution":"Bytte av reim i butikk inntil 2 år etter salgsdato. Autorisert kreditering inn 1 stk reim og ut 1 stk reim."},
						{"condition":"Alta / Alta HR", "resolution":"Bytte av reim i butikk inntil 2 år etter salgsdato. Autorisert kreditering inn 1 stk reim og ut 1 stk reim."},
						{"condition":"Andre klokker", "resolution":"Byttes over disk inntil 2 år etter salgsdato."}
					]
				},
				{
					"brand":"Garmin",
					"routines": [
						{"condition":"Under 1500 kr", "resolution":"Byttes over disk inntil 2 år etter salgsdato."},
						{"condition":"Over 1500 kr", "resolution":"Sendes til verksted for reparasjon."}
					]
				},
				{
					"brand":"Geneva",
					"routines": [
						{"condition":"Under 1000 kr", "resolution":"Byttes over disk inntil 2 år etter salgsdato."},
						{"condition":"Over 1000 kr", "resolution":"Sendes til verksted for reparasjon."}
					]
				},
				{
					"brand":"Gigaset",
					"routines": [
						{"condition":"Under 1000 kr", "resolution":"Byttes over disk inntil 2 år etter salgsdato."},
						{"condition":"Over 1000 kr", "resolution":"Sendes til verksted for reparasjon."}
					]
				},
				{
					"brand":"Google",
					"routines": [
						{"condition":"Under 1000 kr", "resolution":"Byttes over disk inntil 2 år etter salgsdato."},
						{"condition":"Over 1000 kr", "resolution":"Sendes til verksted for reparasjon."}
					]
				},
				{
					"brand":"GoPro",
					"routines": [
						{"condition":"Tilbehør til under 1000 kr", "resolution":"Byttes over disk inntil 2 år etter salgsdato."},
						{"condition":"Over 1000 kr", "resolution":"Sendes til verksted for reparasjon."}
					]
				},
				{
					"brand":"Gorenje",
					"routines": [
						{"condition":"", "resolution":"Skal repareres av verksted. Hjemmeservice kan være tilgjengelig for store produkter."}
					]
				},
				{
					"brand":"Gram",
					"routines": [
						{"condition":"", "resolution":"Skal repareres av verksted. Hjemmeservice kan være tilgjengelig for store produkter."}
					]
				},
				{
					"brand":"Grundig",
					"routines": [
						{"condition":"Under 1000 kr", "resolution":"Byttes over disk inntil 2 år etter salgsdato."},
						{"condition":"Over 1000 kr", "resolution":"Sendes til verksted for reparasjon."}
					]
				},
				{
					"brand":"Harman/Kardon",
					"routines": [
						{"condition":"", "resolution":"Legg inn varekoden direkte i eXchange for å kontrollere om produktet byttes i butikk (opptil 2 år) eller om det skal sendes til reparasjon."}
					]
				},
				{
					"brand":"Hoover",
					"routines": [
						{"condition":"", "resolution":"Alt sendes til verksted for reparasjon, inkludert tilbehør fra salgspakken."}
					]
				},
				{
					"brand":"HP",
					"routines": [
						{"condition":"Tilbehør til under 1000 kr", "resolution":"Byttes over disk inntil 2 år etter salgsdato."},
						{"condition":"Printere til under 1500 kr", "resolution":"Byttes over disk inntil 2 år etter salgsdato."},
						{"condition":"Alt annet", "resolution":"Sendes til verksted for reparasjon."}
					]
				},
				{
					"brand":"HTC",
					"routines": [
						{"condition":"", "resolution":"Alt sendes til verksted for reparasjon, inkludert tilbehør fra salgspakken."}
					]
				},
				{
					"brand":"Huawei",
					"routines": [
						{"condition":"", "resolution":"Alt sendes til verksted for reparasjon, inkludert tilbehør fra salgspakken."}
					]
				},
				{
					"brand":"Husqvarna",
					"routines": [
						{"condition":"Under 1500 kr", "resolution":"Byttes over disk inntil 2 år etter salgsdato."},
						{"condition":"Over 1500 kr", "resolution":"Skal repareres av verksted. Hjemmeservice kan være tilgjengelig for store produkter."}
					]
				},
				{
					"brand":"HyperX",
					"routines": [
						{"condition":"", "resolution":"Byttes alltid over disk inntil 2 år etter salgsdato."}
					]
				},
				{
					"brand":"Ideal of Sweden",
					"routines": [
						{"condition":"", "resolution":"Byttes alltid over disk inntil 2 år etter salgsdato."}
					]
				},
				{
					"brand":"iRobot",
					"routines": [
						{"condition":"", "resolution":"Alt sendes til verksted for reparasjon, inkludert tilbehør fra salgspakken."}
					]
				},
				{
					"brand":"Jabra",
					"routines": [
						{"condition":"Under 1500 kr", "resolution":"Byttes over disk inntil 2 år etter salgsdato."},
						{"condition":"Over 1500 kr", "resolution":"Butikken tar inn produktet og søker kredit/ombytte. Byttes med kunde senere hvis kravet godkjennes."}
					]
				},
				{
					"brand":"Jaybird",
					"routines": [
						{"condition":"Under 1500 kr", "resolution":"Byttes over disk inntil 2 år etter salgsdato."},
						{"condition":"Over 1500 kr", "resolution":"Butikken tar inn produktet og søker kredit/ombytte. Byttes med kunde senere hvis kravet godkjennes."}
					]
				},
				{
					"brand":"JBL",
					"routines": [
						{"condition":"", "resolution":"Legg inn varekoden direkte i eXchange for å kontrollere om produktet byttes i butikk (opptil 2 år) eller om det skal sendes til reparasjon."}
					]
				},
				{
					"brand":"Jordan",
					"routines": [
						{"condition":"Under 1000 kr", "resolution":"Byttes over disk inntil 2 år etter salgsdato."},
						{"condition":"Over 1000 kr", "resolution":"Sendes til verksted for reparasjon."}
					]
				},
				{
					"brand":"Just Wireless",
					"routines": [
						{"condition":"", "resolution":"Byttes alltid over disk inntil 2 år etter salgsdato."}
					]
				},
				{
					"brand":"Kenwood",
					"routines": [
						{"condition":"Under 1000 kr", "resolution":"Byttes over disk inntil 2 år etter salgsdato."},
						{"condition":"Over 1000 kr", "resolution":"Sendes til verksted for reparasjon."}
					]
				},
				{
					"brand":"KEF",
					"routines": [
						{"condition":"Under 1500 kr", "resolution":"Byttes over disk inntil 2 år etter salgsdato."},
						{"condition":"Over 1500 kr", "resolution":"Sendes til verksted for reparasjon."}
					]
				},
				{
					"brand":"KitchenAid",
					"routines": [
						{"condition":"Under 1000 kr", "resolution":"Byttes over disk inntil 2 år etter salgsdato."},
						{"condition":"Over 1000 kr", "resolution":"Sendes til verksted for reparasjon."}
					]
				},
				{
					"brand":"Kulz",
					"routines": [
						{"condition":"", "resolution":"Byttes alltid over disk inntil 2 år etter salgsdato."}
					]
				},
				{
					"brand":"Kurio",
					"routines": [
						{"condition":"Under 1500 kr", "resolution":"Byttes over disk inntil 2 år etter salgsdato."},
						{"condition":"Over 1500 kr", "resolution":"Butikken tar inn produktet og søker kredit/ombytte. Byttes med kunde senere hvis kravet godkjennes."}
					]
				},
				{
					"brand":"Kygo",
					"routines": [
						{"condition":"", "resolution":"Byttes alltid over disk inntil 2 år etter salgsdato."}
					]
				},
				{
					"brand":"König",
					"routines": [
						{"condition":"", "resolution":"Byttes alltid over disk inntil 2 år etter salgsdato."}
					]
				},
				{
					"brand":"Landmann",
					"routines": [
						{"condition":"Tilbehør til under 1000 kr", "resolution":"Byttes over disk inntil 2 år etter salgsdato."},
						{"condition":"Over 1000 kr", "resolution":"Butikken tar inn produktet og søker kredit/ombytte. Byttes med kunde senere hvis kravet godkjennes."}
					]
				},
				{
					"brand":"Lexar",
					"routines": [
						{"condition":"", "resolution":"Byttes alltid over disk inntil 2 år etter salgsdato."}
					]
				},
				{
					"brand":"LG",
					"routines": [
						{"condition":"Under 1000 kr", "resolution":"Byttes over disk inntil 2 år etter salgsdato."},
						{"condition":"Over 1000 kr", "resolution":"Sendes til verksted for reparasjon."},
						{"condition":"TV", "resolution":"Alt sendes til verksted for reparasjon, inkludert tilbehør fra salgspakken. Hjemmeservice er tilgjengelig for TVer større enn 32 tommer."}
					]
				},
				{
					"brand":"Linksys",
					"routines": [
						{"condition":"Under 1000 kr", "resolution":"Byttes over disk inntil 2 år etter salgsdato."},
						{"condition":"Over 1000 kr", "resolution":"Sendes til verksted for reparasjon."}
					]
				},
				{
					"brand":"Logitech",
					"routines": [
						{"condition":"Under 1500 kr", "resolution":"Byttes over disk inntil 2 år etter salgsdato."},
						{"condition":"Over 1500 kr", "resolution":"Butikken tar inn produktet og søker kredit/ombytte. Byttes med kunde senere hvis kravet godkjennes."}
					]
				},
				{
					"brand":"Meloni",
					"routines": [
						{"condition":"", "resolution":"Byttes alltid over disk inntil 2 år etter salgsdato."}
					]
				},
				{
					"brand":"Microsoft",
					"routines": [
						{"condition":"Tilbehør til XBOX", "resolution":"Kunden må selv kontakte Microsoft forbrukersupport."},
						{"condition":"Annet tilbehør under 1000 kr", "resolution":"Byttes over disk inntil 2 år etter salgsdato."},
						{"condition":"Alt annet", "resolution":"Sendes til verksted for reparasjon."}
					]
				},
				{
					"brand":"Miele",
					"routines": [
						{"condition":"Under 1000 kr", "resolution":"Byttes over disk inntil 2 år etter salgsdato."},
						{"condition":"Over 1000 kr", "resolution":"Sendes til verksted for reparasjon."},
						{"condition":"Støvsugere over 1000 kr", "resolution":"Butikken tar inn produktet og søker kredit/ombytte. Byttes med kunde senere hvis kravet godkjennes."}
					]
				},
				{
					"brand":"Mill",
					"routines": [
						{"condition":"Under 2000 kr", "resolution":"Byttes over disk inntil 2 år etter salgsdato."},
						{"condition":"Over 2000 kr", "resolution":"Butikken tar inn produktet og søker kredit/ombytte. Byttes med kunde senere hvis kravet godkjennes."}
					]
				},
				{
					"brand":"Moccamaster",
					"routines": [
						{"condition":"Under 1000 kr", "resolution":"Byttes over disk inntil 2 år etter salgsdato."},
						{"condition":"Over 1000 kr", "resolution":"Sendes til verksted for reparasjon."}
					]
				},
				{
					"brand":"Monster",
					"routines": [
						{"condition":"Under 1000 kr", "resolution":"Byttes over disk inntil 2 år etter salgsdato."},
						{"condition":"Over 1000 kr", "resolution":"Sendes til verksted for reparasjon."}
					]
				},
				{
					"brand":"Nespresso",
					"routines": [
						{"condition":"", "resolution":"All kontakt foregår via Nespresso Club på tlf 80087600. Nespresso ønsker helst at kunden selv tar kontakt."}
					]
				},
				{
					"brand":"Netgear",
					"routines": [
						{"condition":"", "resolution":"Byttes alltid over disk inntil 2 år etter salgsdato."}
					]
				},
				{
					"brand":"Nikon",
					"routines": [
						{"condition":"", "resolution":"Alt sendes til verksted for reparasjon, inkludert tilbehør fra salgspakken."}
					]
				},
				{
					"brand":"Nilfisk",
					"routines": [
						{"condition":"Elite / Extreme modellene", "resolution":"Sendes til verksted for reparasjon."},
						{"condition":"Alt annet", "resolution":"Byttes over disk inntil 2 år etter salgsdato."}
					]
				},
				{
					"brand":"Nintendo",
					"routines": [
						{"condition":"", "resolution":"Alt sendes til verksted for reparasjon, inkludert tilbehør fra salgspakken."}
					]
				},
				{
					"brand":"Noeson",
					"routines": [
						{"condition":"", "resolution":"Byttes alltid over disk inntil 2 år etter salgsdato."}
					]
				},
				{
					"brand":"Nokia",
					"routines": [
						{"condition":"", "resolution":"Alt sendes til verksted for reparasjon, inkludert tilbehør fra salgspakken."}
					]
				},
				{
					"brand":"Nooa",
					"routines": [
						{"condition":"", "resolution":"Byttes alltid over disk inntil 2 år etter salgsdato."}
					]
				},
				{
					"brand":"Noosy",
					"routines": [
						{"condition":"", "resolution":"Byttes alltid over disk inntil 2 år etter salgsdato."}
					]
				},
				{
					"brand":"Nosh",
					"routines": [
						{"condition":"", "resolution":"Byttes alltid over disk inntil 2 år etter salgsdato."}
					]
				},
				{
					"brand":"Olympus",
					"routines": [
						{"condition":"Tilbehør til under 1000 kr", "resolution":"Byttes over disk inntil 2 år etter salgsdato."},
						{"condition":"Over 1000 kr", "resolution":"Sendes til verksted for reparasjon."}
					]
				},
				{
					"brand":"Oral-B",
					"routines": [
						{"condition":"Under 1000 kr veiledende pris", "resolution":"Byttes over disk inntil 2 år etter salgsdato."},
						{"condition":"Over 1000 kr veiledende pris", "resolution":"Sendes til verksted for reparasjon/ombytte."}
					]
				},
				{
					"brand":"Panasonic",
					"routines": [
						{"condition":"Under 1000 kr", "resolution":"Byttes over disk inntil 2 år etter salgsdato."},
						{"condition":"Over 1000 kr", "resolution":"Sendes til verksted for reparasjon."}
					]
				},
				{
					"brand":"PanzerGlass",
					"routines": [
						{"condition":"", "resolution":"Byttes alltid over disk inntil 2 år etter salgsdato."}
					]
				},
				{
					"brand":"Philips",
					"routines": [
						{"condition":"", "resolution":"Legg inn varekoden direkte i eXchange for å kontrollere om produktet byttes i butikk (opptil 2 år) eller om det skal sendes til reparasjon."}
					]
				},
				{
					"brand":"Pinell",
					"routines": [
						{"condition":"Under 1000 kr", "resolution":"Byttes over disk inntil 2 år etter salgsdato."},
						{"condition":"Over 1000 kr", "resolution":"Sendes til verksted for reparasjon."}
					]
				},
				{
					"brand":"Platronics",
					"routines": [
						{"condition":"Under 1500 kr", "resolution":"Byttes over disk inntil 2 år etter salgsdato."},
						{"condition":"Over 1500 kr", "resolution":"Butikken tar inn produktet og søker kredit/ombytte. Byttes med kunde senere hvis kravet godkjennes."}
					]
				},
				{
					"brand":"PlayStation",
					"routines": [
						{"condition":"Håndkontrollere", "resolution":"Byttes over disk inntil 2 år etter salgsdato."},
						{"condition":"Konsoll", "resolution":"Sendes til verksted for reparasjon."}
					]
				},
				{
					"brand":"PocketBook",
					"routines": [
						{"condition":"", "resolution":"Byttes alltid over disk inntil 2 år etter salgsdato."}
					]
				},
				{
					"brand":"Point",
					"routines": [
						{"condition":"Hvitevarer", "resolution":"Sendes til verksted for reparasjon."},
						{"condition":"Alt annet", "resolution":"Byttes over disk inntil 2 år etter salgsdato."}
					]
				},
				{
					"brand":"Polar",
					"routines": [
						{"condition":"", "resolution":"Alt sendes til verksted for reparasjon, inkludert tilbehør fra salgspakken."}
					]
				},
				{
					"brand":"Polaroid",
					"routines": [
						{"condition":"Under 1500 kr", "resolution":"Byttes over disk inntil 2 år etter salgsdato."},
						{"condition":"Over 1500 kr", "resolution":"Sendes til verksted for reparasjon."}
					]
				},
				{
					"brand":"Polk",
					"routines": [
						{"condition":"", "resolution":"Butikk søker kredit/ombytte. Produktet sendes inn til kontroll. Byttes hvis kravet godkjennes."}
					]
				},
				{
					"brand":"Princess",
					"routines": [
						{"condition":"", "resolution":"Byttes alltid over disk inntil 2 år etter salgsdato."}
					]
				},
				{
					"brand":"Popsockets",
					"routines": [
						{"condition":"", "resolution":"Byttes alltid over disk inntil 2 år etter salgsdato."}
					]
				},
				{
					"brand":"Powerbase",
					"routines": [
						{"condition":"", "resolution":"Byttes alltid over disk inntil 2 år etter salgsdato."}
					]
				},
				{
					"brand":"Puro",
					"routines": [
						{"condition":"", "resolution":"Byttes alltid over disk inntil 2 år etter salgsdato."}
					]
				},
				{
					"brand":"Raspberry",
					"routines": [
						{"condition":"Under 1000 kr", "resolution":"Byttes over disk inntil 2 år etter salgsdato."},
						{"condition":"Over 1000 kr", "resolution":"Butikken tar inn produktet og søker kredit/ombytte. Byttes med kunde senere hvis kravet godkjennes."}
					]
				},
				{
					"brand":"Razer",
					"routines": [
						{"condition":"Under 1500 kr", "resolution":"Byttes over disk inntil 2 år etter salgsdato."},
						{"condition":"Over 1500 kr", "resolution":"Sendes til verksted for reparasjon."}
					]
				},
				{
					"brand":"Remington",
					"routines": [
						{"condition":"Under 1000 kr", "resolution":"Byttes over disk inntil 2 år etter salgsdato."},
						{"condition":"Over 1000 kr", "resolution":"Sendes til verksted for reparasjon."}
					]
				},
				{
					"brand":"Retro",
					"routines": [
						{"condition":"Under 1500 kr", "resolution":"Byttes over disk inntil 2 år etter salgsdato."},
						{"condition":"Over 1500 kr", "resolution":"Butikken tar inn produktet og søker kredit/ombytte. Byttes med kunde senere hvis kravet godkjennes."}
					]
				},
				{
					"brand":"Roccat",
					"routines": [
						{"condition":"Under 1000 kr", "resolution":"Byttes over disk inntil 2 år etter salgsdato."},
						{"condition":"Over 1000 kr", "resolution":"Butikken tar inn produktet og søker kredit/ombytte. Byttes med kunde senere hvis kravet godkjennes."}
					]
				},
				{
					"brand":"Salora",
					"routines": [
						{"condition":"", "resolution":"Byttes alltid over disk inntil 2 år etter salgsdato."}
					]
				},
				{
					"brand":"Samsonite",
					"routines": [
						{"condition":"", "resolution":"Byttes alltid over disk inntil 2 år etter salgsdato."}
					]
				},
				{
					"brand":"Samsung",
					"routines": [
						{"condition":"", "resolution":"Alt sendes til verksted for reparasjon, inkludert tilbehør fra salgspakken."}
					]
				},
				{
					"brand":"SanDisk",
					"routines": [
						{"condition":"", "resolution":"Byttes alltid over disk inntil 2 år etter salgsdato."}
					]
				},
				{
					"brand":"SBS",
					"routines": [
						{"condition":"", "resolution":"Byttes alltid over disk inntil 2 år etter salgsdato."}
					]
				},
				{
					"brand":"Scansonic",
					"routines": [
						{"condition":"", "resolution":"Byttes alltid over disk inntil 2 år etter salgsdato."}
					]
				},
				{
					"brand":"Scholl",
					"routines": [
						{"condition":"", "resolution":"Byttes over disk, med unntak av hvitevarer!"}
					]
				},
				{
					"brand":"SENZ",
					"routines": [
						{"condition":"Hvitevarer", "resolution":"Sendes til verksted for reparasjon."},
						{"condition":"Alt annet", "resolution":"Byttes over disk inntil 2 år etter salgsdato."}
					]
				},
				{
					"brand":"Shirui",
					"routines": [
						{"condition":"", "resolution":"Byttes alltid over disk inntil 2 år etter salgsdato."}
					]
				},
				{
					"brand":"Siemens",
					"routines": [
						{"condition":"", "resolution":"Skal repareres av verksted. Hjemmeservice kan være tilgjengelig for store produkter."}
					]
				},
				{
					"brand":"Silverline",
					"routines": [
						{"condition":"", "resolution":"Alt sendes til verksted for reparasjon, inkludert tilbehør fra salgspakken."}
					]
				},
				{
					"brand":"Sony",
					"routines": [
						{"condition":"Tilbehør, under 1000 kr", "resolution":"Byttes over disk inntil 2 år etter salgsdato."},
						{"condition":"Mobiltelefoner", "resolution":"Alt sendes til verksted for reparasjon, inkludert tilbehør fra salgspakken."},
						{"condition":"Lyd & bilde, under 1000 kr", "resolution":"Byttes over disk inntil 2 år etter salgsdato."},
						{"condition":"Lyd & bilde, over 1000 kr", "resolution":"Sendes til verksted for reparasjon."},
						{"condition":"TV LCD/LED", "resolution":"Skal repareres av verksted. Hjemmeservice kan være tilgjengelig for store produkter."}
					]
				},
				{
					"brand":"Stadler Form",
					"routines": [
						{"condition":"Under 1500 kr", "resolution":"Byttes over disk inntil 2 år etter salgsdato."},
						{"condition":"Over 1500 kr", "resolution":"Butikken tar inn produktet og søker kredit/ombytte. Byttes med kunde senere hvis kravet godkjennes."}
					]
				},
				{
					"brand":"Steelseries",
					"routines": [
						{"condition":"Under 1500 kr", "resolution":"Byttes over disk inntil 2 år etter salgsdato."},
						{"condition":"Over 1500 kr", "resolution":"Butikken tar inn produktet og søker kredit/ombytte. Byttes med kunde senere hvis kravet godkjennes."}
					]
				},
				{
					"brand":"Tefal",
					"routines": [
						{"condition":"Under 1000 kr", "resolution":"Byttes over disk inntil 2 år etter salgsdato."},
						{"condition":"Over 1000 kr", "resolution":"Butikken tar inn produktet og søker kredit/ombytte. Byttes med kunde senere hvis kravet godkjennes."}
					]
				},
				{
					"brand":"Temptech",
					"routines": [
						{"condition":"Under 1000 kr", "resolution":"Byttes over disk inntil 2 år etter salgsdato."},
						{"condition":"Over 1000 kr", "resolution":"Skal repareres av verksted. Hjemmeservice kan være tilgjengelig for store produkter."}
					]
				},
				{
					"brand":"Tipi",
					"routines": [
						{"condition":"", "resolution":"Byttes alltid over disk inntil 2 år etter salgsdato."}
					]
				},
				{
					"brand":"Thomson",
					"routines": [
						{"condition":"", "resolution":"Byttes alltid over disk inntil 2 år etter salgsdato."}
					]
				},
				{
					"brand":"Tivoli",
					"routines": [
						{"condition":"Under 1500 kr", "resolution":"Byttes over disk inntil 2 år etter salgsdato."},
						{"condition":"Over 1500 kr", "resolution":"Sendes til verksted for reparasjon."}
					]
				},
				{
					"brand":"TomTom",
					"routines": [
						{"condition":"", "resolution":"Alt sendes til verksted for reparasjon, inkludert tilbehør fra salgspakken."}
					]
				},
				{
					"brand":"Toshiba",
					"routines": [
						{"condition":"Under 1000 kr", "resolution":"Byttes over disk inntil 2 år etter salgsdato."},
						{"condition":"Over 1000 kr", "resolution":"Sendes til verksted for reparasjon."}
					]
				},
				{
					"brand":"TP-Link",
					"routines": [
						{"condition":"", "resolution":"Byttes alltid over disk inntil 2 år etter salgsdato."}
					]
				},
				{
					"brand":"Triacle",
					"routines": [
						{"condition":"", "resolution":"Byttes alltid over disk inntil 2 år etter salgsdato."}
					]
				},
				{
					"brand":"Tristar",
					"routines": [
						{"condition":"", "resolution":"Byttes alltid over disk inntil 2 år etter salgsdato."}
					]
				},
				{
					"brand":"Trust",
					"routines": [
						{"condition":"Under 1500 kr", "resolution":"Byttes over disk inntil 2 år etter salgsdato."},
						{"condition":"Over 1500 kr", "resolution":"Butikken tar inn produktet og søker kredit/ombytte. Byttes med kunde senere hvis kravet godkjennes."}
					]
				},
				{
					"brand":"Turtle Beach",
					"routines": [
						{"condition":"", "resolution":"Byttes alltid over disk inntil 2 år etter salgsdato."}
					]
				},
				{
					"brand":"Ubiquiti",
					"routines": [
						{"condition":"Under 1000 kr", "resolution":"Byttes over disk inntil 2 år etter salgsdato."},
						{"condition":"Over 1000 kr", "resolution":"Butikken tar inn produktet og søker kredit/ombytte. Byttes med kunde senere hvis kravet godkjennes."}
					]
				},
				{
					"brand":"Urban",
					"routines": [
						{"condition":"", "resolution":"Byttes alltid over disk inntil 2 år etter salgsdato."}
					]
				},
				{
					"brand":"Volta",
					"routines": [
						{"condition":"", "resolution":"Byttes alltid over disk inntil 2 år etter salgsdato."}
					]
				},
				{
					"brand":"ZTE",
					"routines": [
						{"condition":"", "resolution":"Alt sendes til verksted for reparasjon, inkludert tilbehør fra salgspakken."}
					]
				},
				{
					"brand":"Wahl",
					"routines": [
						{"condition":"", "resolution":"Byttes alltid over disk inntil 2 år etter salgsdato."}
					]
				},
				{
					"brand":"Wilfa",
					"routines": [
						{"condition":"Under 1000 kr", "resolution":"Byttes over disk inntil 2 år etter salgsdato."},
						{"condition":"Over 1000 kr", "resolution":"Butikken tar inn produktet og søker kredit/ombytte. Byttes med kunde senere hvis kravet godkjennes."}
					]
				},
				{
					"brand":"Whirlpool",
					"routines": [
						{"condition":"Under 1000 kr", "resolution":"Byttes over disk inntil 2 år etter salgsdato."},
						{"condition":"Over 1000 kr", "resolution":"Skal repareres av verksted. Hjemmeservice kan være tilgjengelig for store produkter."}
					]
				},
				{
					"brand":"Wowcase",
					"routines": [
						{"condition":"", "resolution":"Byttes alltid over disk inntil 2 år etter salgsdato."}
					]
				},
				{
					"brand":"XBOX",
					"routines": [
						{"condition":"Tilbehør / håndkontrollere", "resolution":"Kunden må selv kontakte Microsoft forbrukersupport."},
						{"condition":"Konsollen", "resolution":"Sendes til verksted for reparasjon."}
					]
				},
				{
					"brand":"Xipin",
					"routines": [
						{"condition":"", "resolution":"Byttes alltid over disk inntil 2 år etter salgsdato."}
					]
				},
				{
					"brand":"Zanussi",
					"routines": [
						{"condition":"Under 1500 kr", "resolution":"Byttes over disk inntil 2 år etter salgsdato."},
						{"condition":"Over 1500 kr", "resolution":"Skal repareres av verksted. Hjemmeservice kan være tilgjengelig for store produkter."}
					]
				}
			]
		}
		for (i in myObj.brands) {
			x += '<div class="brand"><span class="brand_title">' + myObj.brands[i].brand + '</span>';
			for (j in myObj.brands[i].routines) {
				x += '<div class="brand_routines"><div>' + myObj.brands[i].routines[j].condition + '</div><div>' + myObj.brands[i].routines[j].resolution + '</div></div>';
			}
			x += '</div>';
		}
		$('#brandslist').prepend(x);
			
		$('#list_loading').fadeOut('fast', function(){
			$('#brandseearch').show();
			$('#brandslist').show();
			$('#brandseearch_brandname').focus();
		});
	});
	// Filter brands by input in search bar
	$('#brandseearch_brandname').on('input', function (event) {
		var searchstring = $(this).text();
		if ( (searchstring.length > 1) && (searchstring.match(/^\S+/i).length != 0) ) {
			$('#brandslist div.brand').hide();
			$.each($('#brandslist div.brand span.brand_title'),function() {
				var brandname = $(this).text().replace(/\s/g,'');
				var searchstring_lc = searchstring.toLowerCase().replace(/\s/g,'');
				if (brandname.toLowerCase().match(searchstring_lc)) {
					$(this).closest('div.brand').show();
				}
			});
		} else {
			$('#brandslist div.brand').show();
		}
	});
});