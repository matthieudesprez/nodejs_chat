<?php

include('../base_url.php');

include(BASE_URL.'/conf/conf.php');

include(BASE_URL.'/conf/connexion.php');

include(BASE_URL.'/conf/fonctions.php');



include(BASE_URL.WORK_DIR.'/config/langues.cfg.php');

include(BASE_URL.WORK_DIR.'/includes/session.php');

include(BASE_URL.WORK_DIR.'/config/grid.conf.php');



$_SESSION['langue'] = DEFAULT_LANG;

$environnement_agence['menu'] = array('active' => 0);



// Tableau de sélection du menu lié en fonction de la langue (il faut indiquer les 2 (menu et page) pour les pages hybrides

$array_menu_sel_langue = array('fr' => 13);$array_page_sel_langue = array('fr' => 1);



$id_menu_sel = !empty( $array_menu_sel_langue[$_SESSION['langue']] ) ? $array_menu_sel_langue[$_SESSION['langue']] : '';

$id_page_sel = !empty( $array_page_sel_langue[$_SESSION['langue']] ) ? $array_page_sel_langue[$_SESSION['langue']] : '';



if(!empty($id_page_sel)){

    $result_select_menu_sel = $PDO->query("SELECT *

    FROM dyna_menu

    JOIN dyna_page ON dyna_page.id_page = dyna_menu.id_page

    WHERE dyna_page.id_page = ".$PDO->quote($id_page_sel)."

    AND publier_menu = '1'");

    $compt_select_menu_sel = $result_select_menu_sel->rowCount();



    if(!empty($compt_select_menu_sel)){

        $ligne_select_menu_sel = $result_select_menu_sel->fetch();



// On indique le menu selectionné en fonction de la page affichée

        $id_menu_sel = $ligne_select_menu_sel['id_menu'];

    }

}



// Contient la récupération des menus / sous menu / catégorie des commentaires

include(BASE_URL.WORK_DIR.'/includes/sql_commun.php');



// Requete des blocs (pour indiquer les bonnes images etc).

$result_blocs = $PDO->prepare("

    SELECT *

    FROM dyna_page

    LEFT JOIN dyna_bloc ON dyna_page.id_page = dyna_bloc.id_page

    WHERE dyna_page.id_page = :id_page

    AND publier_page = '1'

    ORDER BY dyna_bloc.posy_bloc ASC, dyna_bloc.posx_bloc ASC");

$result_blocs -> execute(array(

    'id_page' => $id_page_sel

));

$nb_blocs = $result_blocs -> rowCount();

$result_blocs = $result_blocs -> fetchAll();



$array_id_bloc_image = array(0);



foreach( $result_blocs as $line_bloc ){

    if( $line_bloc[ 'type_bloc' ] == 'image' || $line_bloc[ 'type_bloc' ] == 'slider' ){

        $array_id_bloc_image[] = $line_bloc[ 'id_bloc' ];

    }

}



$qMarks = str_repeat('?,', count($array_id_bloc_image) - 1) . '?';



/* Selection des image et des datas liées data pour toutes les images de cette page */

$select_image_data = $PDO -> prepare( '

    SELECT image.*,

    image_data.name_imageData, image_data.value_imageData

    FROM image

    LEFT JOIN image_data ON image.id_image = image_data.id_image

    WHERE image.nom_module = "dynapage"

    AND image.id_liaison IN ('.$qMarks.')

    ORDER BY idOrdre_image DESC

');



$select_image_data -> execute( $array_id_bloc_image );

$count_image_data = $select_image_data -> rowCount();

$select_image_data = $select_image_data -> fetchAll();



foreach( $result_blocs as $index_bloc => $line_bloc ){

    if( $line_bloc[ 'type_bloc' ] == 'image' || $line_bloc[ 'type_bloc' ] == 'slider' ){



        $id_image_old = '';

        $index_nb_image_dans_bloc = -1;

        foreach( $select_image_data as $line_image ){



            if( $line_image[ 'id_liaison' ] == $line_bloc[ 'id_bloc' ] ){



                /* Si c'est la première ligne image trouvé pour le bloc en cours */

                if( $id_image_old != $line_image[ 'id_image' ] ){

                    $index_nb_image_dans_bloc ++;



                    /* Création de la partie "image" dans la line_bloc en cours */

                    $result_blocs[ $index_bloc ][ 'images' ][ $index_nb_image_dans_bloc ][ 'nom' ] = $line_image[ 'nom_image' ];

                    $id_image_old = $line_image[ 'id_image' ];

                }

                $result_blocs[ $index_bloc ][ 'images' ][ $index_nb_image_dans_bloc ][ 'datas' ][ $line_image[ 'name_imageData' ] ] = $line_image[ 'value_imageData' ];

            }

        }

    }

}





/* ############################################### Référencement par défaut ############################################### */



$seo['upline'] = $seo['title'] = 'Liste agence | maisons-kerbea';

$seo['keywords'] = $seo['description'] = $seo['baseline'] = '';



/* ######################################################################################################################## */



/* Récupération du référencement */

$select_referencement = $PDO -> query('SELECT * FROM seo JOIN dyna_page ON seo.seo_id = dyna_page.seo_id WHERE id_page = '.$PDO -> quote($id_page_sel));

$count_referencement = $select_referencement -> rowCount();



// Si une ligne de référencement existe

if(!empty($count_referencement)){

// On indique qu'on a pas besoin du référencement par défaut

    $referencement_trouve = true;



// remplissage du référencement

    $ligne_referencement = $select_referencement -> fetch();

    $seo['title'] = !empty($ligne_referencement['seo_title']) ? $ligne_referencement['seo_title'] : $seo['title'];

    $seo['description'] = !empty($ligne_referencement['seo_description']) ? $ligne_referencement['seo_description'] : $seo['description'];

    $seo['keywords'] = !empty($ligne_referencement['seo_keywords']) ? $ligne_referencement['seo_keywords'] : $seo['keywords'];

    $seo['upline'] = !empty($ligne_referencement['seo_upline']) ? $ligne_referencement['seo_upline'] : $seo['upline'];

    $seo['baseline'] = !empty($ligne_referencement['seo_baseline']) ? $ligne_referencement['seo_baseline'] : $seo['baseline'];

}



/* ############################################### REQUETES DIVERSES ############################################### */



$select_agence = $PDO -> query('

SELECT nom_agence name, alias_agence alias, adresse_agence adress, codePostal_agence zipcode, telephone_agence tel,ville_agence city, gpsLat_agence gps_lat, gpsLng_agence gps_lng

FROM agence

WHERE actif_agence = 1

ORDER BY idOrdre_agence DESC');

$select_agence = $select_agence -> fetchAll();



foreach( $select_agence as $index => $agence ){

    $select_agence[$index]['link'] = RESSOURCE_URL.'/agence-'.$agence['alias'];

    $select_agence[$index]['img'] = '';

}

$agences_json = count($select_agence) ? json_encode($select_agence) : '[]';







?>



<!DOCTYPE HTML>

<html lang="fr-FR">

	<head>

		<meta charset="UTF-8" />

		<title><?php echo $seo['title']; ?></title>



		<?php include(BASE_URL.WORK_DIR.'/includes/include_css_js_commun.inc.php'); ?>

		<link rel="stylesheet" href="<?php echo RESSOURCE_URL; ?>/css/grid_structure.css.php?id=<?php echo $id_page_sel.'&amp;token='.uniqid(10); ?>" />



        <script src="https://maps.googleapis.com/maps/api/js?v=3.18&libraries=geometry,places&sensor=false"></script>

        <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/js-marker-clusterer/1.0.0/markerclusterer_compiled.js"></script>

        <script src="<?php echo RESSOURCE_URL; ?>/js/map.js"></script>

        <script type="text/javascript">

            var spots = <?php echo $agences_json; ?>;

            var was_posted = <?php echo ('POST' === $_SERVER['REQUEST_METHOD']) ? 'true' : 'false';?>



            $(document).ready(function(){

                var terrain_show = true,

                    maison_show = true,

                    geocoder, autocomplete, marker_adress,

                    radius_map = 10000, circleLatLngBounds = null, circleTest = null;



                function majCarte(){

                    var j = 1;

                    for(var i = 0; i < markers.length; i++){

                        markers[i].setVisible(false);

                        mc.removeMarker(markers[i]); // Obliger de le supprimer pour que le cluster le prenne en compte lors du redraw

                        markers[i].setVisible(true);

                        mc.addMarker(markers[i]); // même raison que removeMarker ci-dessus

                    }



                    mc.redraw();

                }



                /**

                 * Search geocode

                 */





                geocoder = new google.maps.Geocoder();

                var autoCompleteOptions = {

                        // bounds: bounds,

                        // types: ['(regions)'],

                        componentRestrictions: {country: 'fr'}

                    },

                    inputSearch = document.getElementById('searchPlaceAgence');

                autocomplete = new google.maps.places.Autocomplete(inputSearch, autoCompleteOptions);



                $('#search_agence_postal').submit(function (e){

                    e.preventDefault();

                    var adresse = $('#searchPlaceAgence').val();



                    // Si un précédent marqueur de recherche d'adresse existe deja on l'éfface pour éviter de garder une trace

                    if(typeof marker_adress != "undefined"){

                        marker_adress.setMap(null);

                    }



                    geocoder.geocode( { 'address': adresse, region:'fr'}, function(results, status) {

                        if (status == google.maps.GeocoderStatus.OK) {

                            var position = results[0].geometry.location;



                            if(adresse != ''){

                                /* zone radius calcul */

                                // Options de la zone cercle

                                var circleOptions = {

                                    center : position,

                                    map:map,

                                    radius : radius_map,

                                    strokeOpacity : 0,

                                    fillOpacity : 0

                                }



                                // Si un précédent cercle de recherche d'adresse existe deja on l'éfface pour éviter de garder une trace

                                if(circleTest !== null){

                                    circleTest.setMap(null)

                                }



                                // Création du cercle

                                circleTest = new google.maps.Circle(circleOptions);				// Création d'un cercle de zone

                                circleLatLngBounds = circleTest.getBounds();				// Récupération du bounds du cercle pour avoir une zone approximative des marqueurs à afficher



                                map.fitBounds(circleLatLngBounds);

                            }

                            else{

                                if(circleTest !== null){

                                    circleTest.setMap(null)

                                }

                                circleTest = circleLatLngBounds = null;

                            }



                            majCarte();

                        }

                        else{

                            return false;

                        }

                    });

                });



                if(was_posted){



                    var adresse = $('#searchPlaceAgence').val();



                    // Si un précédent marqueur de recherche d'adresse existe deja on l'éfface pour éviter de garder une trace

                    if(typeof marker_adress != "undefined"){

                        marker_adress.setMap(null);

                    }



                    geocoder.geocode( { 'address': adresse, region:'fr'}, function(results, status) {

                        if (status == google.maps.GeocoderStatus.OK) {

                            var position = results[0].geometry.location;



                            if(adresse != ''){

                                /* zone radius calcul */

                                // Options de la zone cercle

                                var circleOptions = {

                                    center : position,

                                    map:map,

                                    radius : radius_map,

                                    strokeOpacity : 0,

                                    fillOpacity : 0

                                }



                                // Si un précédent cercle de recherche d'adresse existe deja on l'éfface pour éviter de garder une trace

                                if(circleTest !== null){

                                    circleTest.setMap(null)

                                }



                                // Création du cercle

                                circleTest = new google.maps.Circle(circleOptions);				// Création d'un cercle de zone

                                circleLatLngBounds = circleTest.getBounds();				// Récupération du bounds du cercle pour avoir une zone approximative des marqueurs à afficher



                                map.fitBounds(circleLatLngBounds);

                            }

                            else{

                                if(circleTest !== null){

                                    circleTest.setMap(null)

                                }

                                circleTest = circleLatLngBounds = null;

                            }



                            majCarte();

                        }

                        else{

                            return false;

                        }

                    });

                };

            });

        </script>



	</head>

    <body>



    <?php include(BASE_URL.WORK_DIR.'/includes/header.inc.php'); ?>

    <div id="container-content">

        <?php if(!empty($environnement_agence['background_image'])):?>

            <div class="img-back-agence no-mobile"><img src="<?php echo $environnement_agence['background_image']; ?>" alt=""/></div>

        <?php endif; ?>



        <div class="content-hover">

            <div class="site-width">

                <div class="breadcrumbs"><a href="<?php echo RESSOURCE_URL; ?>">Maisons Kerbéa</a><img src="<?php echo RESSOURCE_URL; ?>/images/pictos/fl-r-red.png" alt=""/><a href="<?php echo RESSOURCE_URL; ?>/liste-agence.php">Carte des agences</a><img src="<?php echo RESSOURCE_URL; ?>/images/pictos/fl-r-red.png" alt=""/><?php echo $title; ?></div>

                <h1 class="black-title no_mrg">Trouvez votre agence</h1>



                <div id="map-container" class="page" style="top:0; background: none; width:100%; box-sizing: border-box;">

                    <div id="gmap" style="width: 100%;"></div>

                </div>

            </div>

        </div>

    </div>



    <?php include(BASE_URL.WORK_DIR.'/includes/footer.inc.php'); ?>
    </body>

</html>