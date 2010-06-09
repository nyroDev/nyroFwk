<?php
$cfg = array(
    'formatDate'=>array(
        'short'=>'D/M/YY',         // 12/31/08
        'short2'=>'D/M/YYYY',      // 12/31/2008
        'medium'=>'D-MM-YY',       // 31-Dec-08
        'long'=>'D MMM YYYY',      // 31 December 2008
        'full'=>'EEE D MMM YYYY',  // Wednesday 31 December 2008
        'fullMed'=>'EE D MM YYYY', // Wed, 31 Dec 2008
    ),
    'formatTime'=>array(
        'short'=>'H:i',            // 00:04
        'medium'=>'H:i:s',         // 00:04:13
        'long'=>'H:i:s',           // 00:04:13
    ),
    'formatDatetime'=>array(
        'long'=>'date à time',    // 31 December 2008 at 00:04:13
        'full'=>'date à time',    // Wednesday 31 December 2008 at 00:04:13
    ),
    'day'=>array(
        'd0'=>array('s'=>'D', 'm'=>'Dim', 'l'=>'Dimanche'),
        'd1'=>array('s'=>'L', 'm'=>'Lun', 'l'=>'Lundi'),
        'd2'=>array('s'=>'M', 'm'=>'Mar', 'l'=>'Mardi'),
        'd3'=>array('s'=>'M', 'm'=>'Mer', 'l'=>'Mercredi'),
        'd4'=>array('s'=>'J', 'm'=>'Jeu', 'l'=>'Jeudi'),
        'd5'=>array('s'=>'V', 'm'=>'Ven', 'l'=>'Vendredi'),
        'd6'=>array('s'=>'S', 'm'=>'Sam', 'l'=>'Samedi'),
    ),
    'month'=>array(
        'm1'=>array('s'=>'J', 'm'=>'Jan', 'l'=>'Janvier'),
        'm2'=>array('s'=>'F', 'm'=>'Fév', 'l'=>'Février'),
        'm3'=>array('s'=>'M', 'm'=>'Mar', 'l'=>'Mars'),
        'm4'=>array('s'=>'A', 'm'=>'Avr', 'l'=>'Avril'),
        'm5'=>array('s'=>'M', 'm'=>'Mai', 'l'=>'Mai'),
        'm6'=>array('s'=>'J', 'm'=>'Jui', 'l'=>'Juin'),
        'm7'=>array('s'=>'J', 'm'=>'Jul', 'l'=>'Juillet'),
        'm8'=>array('s'=>'A', 'm'=>'Aoû', 'l'=>'Août'),
        'm9'=>array('s'=>'S', 'm'=>'Sep', 'l'=>'Septembre'),
        'm10'=>array('s'=>'O', 'm'=>'Oct', 'l'=>'Octobre'),
        'm11'=>array('s'=>'N', 'm'=>'Nov','l'=> 'Novembre'),
        'm12'=>array('s'=>'D', 'm'=>'Déc', 'l'=>'Décembre'),
    ),
    'timeago'=>array(
    	'now'=>'il y a juste un moment',
    	'-'=>array(
    		'y'=>array(
    			'one'=>'il y a un an',
    			'mul'=>'il y a %s ans',
    		),
    		'm'=>array(
    			'one'=>'il y a un mois',
    			'mul'=>'il y a %s mois',
    		),
    		'w'=>array(
    			'one'=>'il y a une semaine',
    			'mul'=>'il y a %s semaines',
    		),
    		'd'=>array(
    			'one'=>'il y a un jour',
    			'mul'=>'il y a %s jours',
    		),
    		'h'=>array(
    			'one'=>'il y a une heure',
    			'mul'=>'il y a %s heures',
    		),
    		'i'=>array(
    			'one'=>'il y a une minute',
    			'mul'=>'il y a %s minutes',
    		),
    		's'=>array(
    			'one'=>'il y a une seconde',
    			'mul'=>'il y a %s secondes',
    		),
    	),
    	'+'=>array(
    		'y'=>array(
    			'one'=>'dans un an',
    			'mul'=>'dans %s ans',
    		),
    		'm'=>array(
    			'one'=>'dans un mois',
    			'mul'=>'dans %s mois',
    		),
    		'w'=>array(
    			'one'=>'dans une semaine',
    			'mul'=>'dans %s semaines',
    		),
    		'd'=>array(
    			'one'=>'dans un jour',
    			'mul'=>'dans %s jours',
    		),
    		'h'=>array(
    			'one'=>'dans une heure',
    			'mul'=>'dans %s heures',
    		),
    		'i'=>array(
    			'one'=>'dans une minute',
    			'mul'=>'dans %s minutes',
    		),
    		's'=>array(
    			'one'=>'dans une seconde',
    			'mul'=>'dans %s secondes',
    		),
    	),
    ),
);
