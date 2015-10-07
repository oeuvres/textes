<?php
ini_set('display_errors', '1');
error_reporting(-1);
include (dirname(__FILE__).'/../teipot/Teipot.php'); // prendre le pot
$pot=new Teipot(dirname(__FILE__).'/oeuvres.sqlite', 'fr'); // mettre le sachet SQLite dans le pot
$pot->file($pot->path); // envoyer les fichiers statiques de la base
$session = new Session($pot); // ouvrir une session
Session::hooks(); // laisser parler divers envois spécifiques à la session (javascript, MS.word…) 

// Si un document correspond à ce chemin, charger un tableau avec différents composants (body, head, breadcrumb…)
$doc=$pot->doc($pot->path);
// rediriger si pas de / final  http://googlewebmastercentral.blogspot.fr/2010/04/to-slash-or-not-to-slash.html
if ($pot->path && substr($pot->path, - strlen($doc['bookname'])) === $doc['bookname']){
  $location='http://'. $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'].'/';
  header("Location: $location",TRUE,301);
}
// pas de body trouvé, charger des résultats en mémoire
if (!isset($doc['body'])) {
  $timeStart=microtime(true);
  $pot->search();
}
/*

  // ajouter à l’historique
  else {
    Session::bookhist(null, $doc);
  }
*/

$teipot = $pot->basehref() .'../teipot/'; // chemin css, js ; baseHref est le nombre de '../' utile pour revenir en racine du site
$theme = $pot->basehref() .'../theme/'; // autres ressources spécifiques


?><!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <meta charset="UTF-8" />
    <?php 
if(isset($doc['head'])) echo $doc['head']; 
else echo '
<title>OBVIL, corpus d’œuvres</title>
';
    ?>
    <link rel="stylesheet" type="text/css" href="<?php echo $teipot; ?>html.css" />
    <link rel="stylesheet" type="text/css" href="<?php echo $teipot; ?>teipot.css" />
    <link rel="stylesheet" type="text/css" href="<?php echo $theme; ?>obvil.css" />
  </head>
  <body>
    <div id="center">
      <header id="header">
        <h1>
          <a href="<?php echo $pot->basehref() . $pot->qsa(null, null, '?'); ?>">OBVIL, corpus d’œuvres</a>
        </h1>
        <a class="logo" href="http://obvil.paris-sorbonne.fr/bibliotheque/"><img class="logo" src="<?php echo $theme; ?>img/logo-obvil.png" alt="OBVIL"></a>
      </header>
      <div id="contenu">
        <main id="main">
          <nav id="toolbar">
            <?php
if (isset($doc['prevnext'])) echo $doc['prevnext'];    
            ?>
          </nav>
          <div id="article">
            <?php
if (isset($doc['body'])) {
  echo $doc['body'];
  // page d’accueil d’un livre avec recherche plein texte, afficher une concordance
  // page d’accueil d’un livre
  if (!isset($doc['artname']) || $doc['artname']=='index') {
    if ($pot->q) {
      $pot->bookrowid = $doc['bookrowid'];
      echo $pot->chrono($doc['bookrowid']);
      echo $pot->concBook($doc['bookrowid']);
    }
  }
}
// pas de livre demandé, montrer un rapport général
else {
  // nombre de résultats
  echo $pot->report();
  // présentation chronologique des résultats
  echo $pot->chrono();
  // présentation bibliographique des résultats
  echo $pot->biblio();
  // concordance s’il y a recherche plein texte
  echo $pot->concByBook();
}
            ?>
          </div>
        </main>
        <aside id="aside">
          <?php
// les concordances peuvent être très lourdes, placer la nav sans attendre
// livre
if (isset($doc['bookrowid'])) {
  if(isset($doc['download'])) echo "\n".'<nav id="download">' . $doc['download'] . '</nav>';

  // auteur, titre, date
  echo "\n".'<header>';
  echo "\n".'<div>';
  if (isset($doc['byline'])) echo $doc['byline'];
  if (isset($doc['end'])) echo ' ('.$doc['end'].')';
  echo '</div>';
  echo "\n".'<a class="title" href="' . $pot->basehref() . $doc['bookname'] . '/">'.$doc['title'].'</a>';
  echo "\n".'</header>';
  // rechercher dans ce livre
  echo '
  <form action=".#conc" name="searchbook" id="searchbook">
    <input name="q" id="q" onclick="this.select()" class="search" size="20" placeholder="Rechercher dans ce livre" title="Rechercher dans ce livre" value="'. str_replace('"', '&quot;', $pot->q) .'"/>
    <input type="image" id="go" alt="&gt;" value="&gt;" name="go" src="'. $theme . 'img/loupe.png"/>
  </form>
  ';
  // table des matières
  echo '
          <div id="toolpan" class="toc">
            <div class="toc">
              '.$doc['toc'].'
            </div>
          </div>
  ';
}
// accueil ? formulaire de recherche général
else {
  echo'
    <form action="">
      <input name="q" class="text" placeholder="Rechercher" value="'.str_replace('"', '&quot;', $pot->q).'"/>
      <div><label>De <input placeholder="année" name="start" class="year" value="'.$pot->start.'"/></label> <label>à <input class="year" placeholder="année" name="end" value="'. $pot->end .'"/></label></div>
      <button type="reset" onclick="return Form.reset(this.form)">Effacer</button>
      <button type="submit">Rechercher</button>
    </form>
  ';
}
          ?>
        </aside>
      </div>
      <?php 
// footer
      ?>
    </div>
    <script type="text/javascript" src="<?php echo $teipot; ?>Tree.js">//</script>
    <script type="text/javascript" src="<?php echo $teipot; ?>Form.js">//</script>
    <script type="text/javascript" src="<?php echo $teipot; ?>Sortable.js">//</script>
    <script type="text/javascript"><?php if (isset($doc['js']))echo $doc['js']; ?></script>  
  </body>
</html>
