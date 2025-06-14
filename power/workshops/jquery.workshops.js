$('a:not([href="#"]):not([target="_blank"])').click(function() {
	$('#page_loading').fadeIn('fast');
});
$(window).on('load', function() {
	function fetchWorkshops() {
		var myObj, i, j, x = "";
		myObj = {
			"brands": [
				{
					"brand":"AEG",
					"workshop":"<a href='http://www.es-kjeden.no/' target='_blank'>ES-kjeden</a> (flere verksteder)<br><a href='https://arendalelektroverksted.no/' target='_blank'>Elektroverkstedet</a><br><a href='http://www.elesco.no/' target='_blank'>Elesco</a>",
					"process":"Kontakt lokalt verksted direkte"
				},
				{
					"brand":"ASKO",
					"workshop":"<a href='http://www.es-kjeden.no/' target='_blank'>ES-kjeden</a> (flere verksteder)<br><a href='https://arendalelektroverksted.no/' target='_blank'>Elektroverkstedet</a>",
					"process":"Kontakt lokalt verksted direkte"
				},
				{
					"brand":"Beha",
					"workshop":"<a href='http://www.beha.no/Servicekontakter' class='locationsbtn' target='_blank'>Verkstedliste</a>",
					"process":"Kontakt lokalt verksted direkte"
				},
				{
					"brand":"Bosch",
					"workshop":"<a href='http://www.es-kjeden.no/' target='_blank'>ES-kjeden</a> (flere verksteder)<br><a href='https://arendalelektroverksted.no/' target='_blank'>Elektroverkstedet</a>",
					"process":"Kontakt lokalt verksted direkte"
				},
				{
					"brand":"Candy",
					"workshop":"-",
					"process":"Tlf 22558222 eller via <a href='http://www.hoover-norge.no/en_GB/contact-service' target='_blank'>hoover-norge.no</a><br><span class='smallfont'>Dette merket har ingen verksted. Legges inn i eXchange for godkjenning.</span>"
				},
				{
					"brand":"Eico",
					"workshop":"-",
					"process":"Bestill service på <a href='https://www.eico.dk/service/' target='_blank'>eico.dk/service</a>"
				},
				{
					"brand":"Electrolux",
					"workshop":"<a href='http://www.es-kjeden.no/' target='_blank'>ES-kjeden</a> (flere verksteder)<br><a href='https://arendalelektroverksted.no/' target='_blank'>Elektroverkstedet</a><br><a href='http://www.elesco.no/' target='_blank'>Elesco</a>",
					"process":"Kontakt lokalt verksted direkte"
				},
				{
					"brand":"Gorenje",
					"workshop":"<a href='http://www.es-kjeden.no/' target='_blank'>ES-kjeden</a> (flere verksteder)<br><a href='https://arendalelektroverksted.no/' target='_blank'>Elektroverkstedet</a>",
					"process":"Kontakt lokalt verksted direkte"
				},
				{
					"brand":"Gram",
					"workshop":"<a href='http://www.es-kjeden.no/' target='_blank'>ES-kjeden</a> (flere verksteder)<br><a href='https://arendalelektroverksted.no/' target='_blank'>Elektroverkstedet</a>",
					"process":"Bestill service på <a href='https://www.gram.no/kundeservice/service-forespoersel' target='_blank'>gram.no</a>"
				},
				{
					"brand":"Grundig",
					"workshop":"<a href='http://www.elesco.no/' target='_blank'>Elesco</a>",
					"process":"Bestill service på <a href='https://www.serviceinfo.se/service/nb-NO/Consumer/Registration/Step1?UserGroupId=81&CustomerCountry=NO' target='_blank'>grundig.com</a>"
				},
				{
					"brand":"Hoover",
					"workshop":"-",
					"process":"Tlf 22558222 eller via <a href='http://www.hoover-norge.no/en_GB/contact-service' target='_blank'>hoover-norge.no</a><br><span class='smallfont'>Dette merket har ingen verksted. Legges inn i eXchange for godkjenning.</span>"
				},
				{
					"brand":"Husqvarna",
					"workshop":"<a href='http://www.es-kjeden.no/' target='_blank'>ES-kjeden</a> (flere verksteder)<br><a href='https://arendalelektroverksted.no/' target='_blank'>Elektroverkstedet</a><br><a href='http://www.elesco.no/' target='_blank'>Elesco</a>",
					"process":"Kontakt lokalt verksted direkte"
				},
				{
					"brand":"Indesit",
					"workshop":"<a href='http://www.es-kjeden.no/' target='_blank'>ES-kjeden</a> (flere verksteder)<br><a href='https://arendalelektroverksted.no/' target='_blank'>Elektroverkstedet</a>",
					"process":"Kontakt lokalt verksted direkte"
				},
				{
					"brand":"LG",
					"workshop":"<a href='http://www.elesco.no/' target='_blank'>Elesco</a>",
					"process":"Kontakt lokalt verksted direkte"
				},
				{
					"brand":"Miele",
					"workshop":"<a href='http://www.es-kjeden.no/' target='_blank'>ES-kjeden</a> (flere verksteder)<br><a href='https://arendalelektroverksted.no/' target='_blank'>Elektroverkstedet</a>",
					"process":"Kontakt lokalt verksted direkte"
				},
				{
					"brand":"Panasonic",
					"workshop":"<a href='http://www.elesco.no/' target='_blank'>Elesco</a>",
					"process":"Kontakt lokalt verksted direkte"
				},
				{
					"brand":"Point",
					"workshop":"<a href='http://www.es-kjeden.no/' target='_blank'>ES-kjeden</a> (flere verksteder)<br><a href='https://arendalelektroverksted.no/' target='_blank'>Elektroverkstedet</a>",
					"process":"Bestill service på <a href='https://www.servicecompaniet.no/ServiceHvitevarer/Create' target='_blank'>servicecompaniet.no</a> / <a href='https://www.gram.no/kundeservice/service-forespoersel' target='_blank'>gram.no</a><br><span class='smallfont'>Hvis modellen ikke finnes hos ServiceCompaniet eller Gram så skal produktet legges inn i eXchange for å bli kreditert.</span>"
				},
				{
					"brand":"Samsung",
					"workshop":"<a href='http://www.elesco.no/' target='_blank'>Elesco</a>",
					"process":"Kontakt Samsung på <a href='https://www.samsung.com/no/support/contact/#contactinfo' target='_blank'>samsung.no</a> eller tlf. 21629099<br><span class='smallfont'>Samsung ønsker at det er kunden som tar kontakt. Forklar kunden at det vil ta mye lengre tid dersom butikken må gjøre dette.</span>"
				},
				{
					"brand":"SENZ",
					"workshop":"<a href='http://www.es-kjeden.no/' target='_blank'>ES-kjeden</a> (flere verksteder)<br><a href='https://arendalelektroverksted.no/' target='_blank'>Elektroverkstedet</a>",
					"process":"Bestill service på <a href='https://www.servicecompaniet.no/ServiceHvitevarer/Create' target='_blank'>servicecompaniet.no</a> / <a href='https://www.gram.no/kundeservice/service-forespoersel' target='_blank'>gram.no</a><br><span class='smallfont'>Hvis modellen ikke finnes hos ServiceCompaniet eller Gram så skal produktet legges inn i eXchange for å bli kreditert.</span>"
				},
				{
					"brand":"Silverline",
					"workshop":"-",
					"process":"Bestill service på <a href='https://www.servicecompaniet.no/ServiceHvitevarer/Create' target='_blank'>servicecompaniet.no</a>"
				},
				{
					"brand":"Temptech",
					"workshop":"-",
					"process":"Bestill service på <a href='https://temptech.no/service/' target='_blank'>temptech.no/service</a>"
				},
				{
					"brand":"Whirlpool",
					"workshop":"<a href='http://www.es-kjeden.no/' target='_blank'>ES-kjeden</a> (flere verksteder)<br><a href='https://arendalelektroverksted.no/' target='_blank'>Elektroverkstedet</a>",
					"process":"Kontakt lokalt verksted direkte"
				},
				{
					"brand":"Witt",
					"workshop":"<a href='http://www.es-kjeden.no/' target='_blank'>ES-kjeden</a> (flere verksteder)<br><a href='https://arendalelektroverksted.no/' target='_blank'>Elektroverkstedet</a>",
					"process":"Bestill service på <a href='https://www.servicecompaniet.no/ServiceHvitevarer/Create' target='_blank'>servicecompaniet.no</a>"
				},
				{
					"brand":"Zanussi",
					"workshop":"<a href='http://www.es-kjeden.no/' target='_blank'>ES-kjeden</a> (flere verksteder)<br><a href='https://arendalelektroverksted.no/' target='_blank'>Elektroverkstedet</a>",
					"process":"Kontakt lokalt verksted direkte"
				}
			]
		}
		for (i in myObj.brands) {
			x += '<div class="brand"><div class="brand_title">' + myObj.brands[i].brand + '</div><div>' + myObj.brands[i].workshop + '</div><div>' + myObj.brands[i].process + '</div></div>';
		}
		$('#workshoplist_brands').prepend(x);
		insertList();
	}
	function insertList() {
		$('#list_loading').fadeOut('fast', function() {
			$('#brandseearch').show();
			$('#workshoplist').show();
			$('#brandseearch_brandname').focus();
		});
	}
	$(document).ready(function($) {
		$('#page_loading').fadeOut('fast');
		fetchWorkshops();
	});
	// Filter brands by input in search bar
	$('#brandseearch_brandname').on('input', function (event) {
		var searchstring = $(this).text();
		if ( (searchstring.length > 1) && (searchstring.match(/^\S+/i).length != 0) ) {
			$('#workshoplist div.brand').hide();
			$.each($('#workshoplist div.brand div.brand_title'),function() {
				var brandname = $(this).text().replace(/\s/g,'');
				var searchstring_lc = searchstring.toLowerCase().replace(/\s/g,'');
				if (brandname.toLowerCase().match(searchstring_lc)) {
					$(this).closest('div.brand').show();
				}
			});
		} else {
			$('#workshoplist div.brand').show();
		}
	});
});