<?php
$cfg = array(
	'timestamp'=>time(),
	'defaultFormat'=>array(
		'type'=>'datetime',
		'len'=>'short'
	),
    'formatDate'=>array(
        'short'=>'M/D/YY',         // 12/31/08
        'short2'=>'M/D/YYYY',      // 12/31/08
        'medium'=>'D-MM-YY',       // 31-Dec-08
        'long'=>'D MMM YYYY',      // 31 December 2008
        'full'=>'EEE D MMM YYYY',  // Wednesday 31 December 2008
        'fullMed'=>'EE, D MM YYYY',// Wed, 31 Dec 2008
        'mysql'=>'YYYY-M-D',       // 2008-12-31
    ),
    'formatTime'=>array(
        'short'=>'h:i',            // 00:04
        'medium'=>'h:i:s',         // 00:04:13
        'long'=>'h:i:s',           // 00:04:13
        'fullMed'=>'h:i:s o',      // 00:04:13 +0200
        'full'=>'h:i:s',           // 00:04:13 +0200
        'mysql'=>'h:i:s',          // 00:04:13
    ),
    'formatDatetime'=>array(
        'short'=>'date time',      // 12/31/08 00:04
        'medium'=>'date time',     // 31-Dec-08 00:04:13
        'long'=>'date at time',    // 31 December 2008 at 00:04:13
        'fullMed'=>'date time',    // Wed, 31 Dec 2008 00:04:13 +0200
        'full'=>'date at time',    // Wednesday 31 December 2008 at 00:04:13
        'mysql'=>'date time',      // 2008-12-31 00:04:13
    ),
    'day'=>array(
        'd0'=>array('s'=>'S', 'm'=>'Sun', 'l'=>'Sunday'),
        'd1'=>array('s'=>'M', 'm'=>'Mon', 'l'=>'Monday'),
        'd2'=>array('s'=>'T', 'm'=>'Tue', 'l'=>'Tuesday'),
        'd3'=>array('s'=>'W', 'm'=>'Wed', 'l'=>'Wednesday'),
        'd4'=>array('s'=>'T', 'm'=>'Thu', 'l'=>'Thursday'),
        'd5'=>array('s'=>'F', 'm'=>'Fri', 'l'=>'Friday'),
        'd6'=>array('s'=>'S', 'm'=>'Sat', 'l'=>'Saturday'),
    ),
    'month'=>array(
        'm1'=>array('s'=>'J', 'm'=>'Jan', 'l'=>'January'),
        'm2'=>array('s'=>'F', 'm'=>'Feb', 'l'=>'February'),
        'm3'=>array('s'=>'M', 'm'=>'Mar', 'l'=>'March'),
        'm4'=>array('s'=>'A', 'm'=>'Apr', 'l'=>'April'),
        'm5'=>array('s'=>'M', 'm'=>'May', 'l'=>'May'),
        'm6'=>array('s'=>'J', 'm'=>'Jun', 'l'=>'June'),
        'm7'=>array('s'=>'J', 'm'=>'Jui', 'l'=>'July'),
        'm8'=>array('s'=>'A', 'm'=>'Aug', 'l'=>'August'),
        'm9'=>array('s'=>'S', 'm'=>'Sep', 'l'=>'September'),
        'm10'=>array('s'=>'O', 'm'=>'Oct', 'l'=>'October'),
        'm11'=>array('s'=>'N', 'm'=>'Nov', 'l'=>'November'),
        'm12'=>array('s'=>'D', 'm'=>'Dec', 'l'=>'December'),
    ),
    'timeago'=>array(
    	'now'=>'a moment ago',
    	'-'=>array(
    		'y'=>array(
    			'one'=>'one year ago',
    			'mul'=>'%s years ago'
    		),
    		'm'=>array(
    			'one'=>'one month ago',
    			'mul'=>'%s months ago'
    		),
    		'w'=>array(
    			'one'=>'one week ago',
    			'mul'=>'%s weeks ago'
    		),
    		'd'=>array(
    			'one'=>'one day ago',
    			'mul'=>'%s days ago'
    		),
    		'h'=>array(
    			'one'=>'one hour ago',
    			'mul'=>'%s hours ago'
    		),
    		'i'=>array(
    			'one'=>'one minute ago',
    			'mul'=>'%s minutes ago'
    		),
    		's'=>array(
    			'one'=>'one second ago',
    			'mul'=>'%s seconds ago'
    		)
    	),
    	'+'=>array(
    		'y'=>array(
    			'one'=>'in one year',
    			'mul'=>'in %s years'
    		),
    		'm'=>array(
    			'one'=>'in one month',
    			'mul'=>'in %s months'
    		),
    		'w'=>array(
    			'one'=>'in one week',
    			'mul'=>'in %s weeks'
    		),
    		'd'=>array(
    			'one'=>'in one day',
    			'mul'=>'in %s days'
    		),
    		'h'=>array(
    			'one'=>'in one hour',
    			'mul'=>'in %s hours'
    		),
    		'i'=>array(
    			'one'=>'in one minute',
    			'mul'=>'in %s minutes'
    		),
    		's'=>array(
    			'one'=>'in one second',
    			'mul'=>'in %s seconds'
    		)
    	)
    )
);
