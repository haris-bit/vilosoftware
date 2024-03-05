 <meta charset="UTF-8">
        <title>Calendar - Anomoz Softwares</title>
        <!-- demo stylesheet -->
    	<link type="text/css" rel="stylesheet" href="media/layout.css" />

	<!-- helper libraries -->
	<script src="js/jquery/jquery-1.9.1.min.js" type="text/javascript"></script>

	<!-- daypilot libraries -->
        <script src="js/daypilot/daypilot-all.min.js" type="text/javascript"></script>

        <link type="text/css" rel="stylesheet" href="icons/style.css" />

        <style type="text/css">
            body, input, button, select {
                font-size: 14px;
            }
            
            select {
                padding: 5px;
            }
            
            .toolbar {
                margin: 10px 0px;
            }
            
            .toolbar button {
                padding: 5px 15px;
            }
            
            .icon {
                font-size: 14px;
                text-align: center;
                line-height: 14px;
                vertical-align: middle;

                cursor: pointer;
            }
            
            .toolbar-separator {
                width: 1px;
                height: 28px;
                /*content: '&nbsp;';*/
                display: inline-block;
                box-sizing: border-box;
                background-color: #ccc;
                margin-bottom: -8px;
                margin-left: 15px;
                margin-right: 15px;
            }

            .scheduler_default_rowheader_inner
            {
                border-right: 1px solid #ccc;
            }
            .scheduler_default_rowheadercol2
            {
                background: White;
            }
            .scheduler_default_rowheadercol2 .scheduler_default_rowheader_inner
            {
                top: 2px;
                bottom: 2px;
                left: 2px;
                background-color: transparent;
                border-left: 5px solid #38761d; /* green */
                border-right: 0px none;
            }
            .status_dirty.scheduler_default_rowheadercol2 .scheduler_default_rowheader_inner
            {
                border-left: 5px solid #cc0000; /* red */
            }
            .status_cleanup.scheduler_default_rowheadercol2 .scheduler_default_rowheader_inner
            {
                border-left: 5px solid #e69138; /* orange */
            }
            
            

        </style>
        
        <?
        error_reporting(0);
ini_set('display_errors', 0);
        ?>
        
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
        <!--<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
        <script src="https://cdn.jsdelivr.net/npm/popper.js@1.14.7/dist/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
        
        
        -->