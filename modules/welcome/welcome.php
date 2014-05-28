<!DOCTYPE html>
<html>
    <head>
        <title>Welcome</title>
        <style>
            html{
                min-height: 100%;
            }
            body{
                margin: 0px;
                padding: 0px;
                height: 100%;
                font-family: "Courier New", Courier, monospace;
                font-size: 12px;
background: #cc0000;
background: url(data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiA/Pgo8c3ZnIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgdmlld0JveD0iMCAwIDEgMSIgcHJlc2VydmVBc3BlY3RSYXRpbz0ibm9uZSI+CiAgPGxpbmVhckdyYWRpZW50IGlkPSJncmFkLXVjZ2ctZ2VuZXJhdGVkIiBncmFkaWVudFVuaXRzPSJ1c2VyU3BhY2VPblVzZSIgeDE9IjAlIiB5MT0iMCUiIHgyPSIwJSIgeTI9IjEwMCUiPgogICAgPHN0b3Agb2Zmc2V0PSIwJSIgc3RvcC1jb2xvcj0iI2NjMDAwMCIgc3RvcC1vcGFjaXR5PSIxIi8+CiAgICA8c3RvcCBvZmZzZXQ9IjEwMCUiIHN0b3AtY29sb3I9IiNjYzAwMDAiIHN0b3Atb3BhY2l0eT0iMSIvPgogIDwvbGluZWFyR3JhZGllbnQ+CiAgPHJlY3QgeD0iMCIgeT0iMCIgd2lkdGg9IjEiIGhlaWdodD0iMSIgZmlsbD0idXJsKCNncmFkLXVjZ2ctZ2VuZXJhdGVkKSIgLz4KPC9zdmc+);
background: -moz-linear-gradient(top,  #cc0000 0%, #cc0000 100%);
background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,#cc0000), color-stop(100%,#cc0000));
background: -webkit-linear-gradient(top,  #cc0000 0%,#cc0000 100%);
background: -o-linear-gradient(top,  #cc0000 0%,#cc0000 100%);
background: -ms-linear-gradient(top,  #cc0000 0%,#cc0000 100%);
background: linear-gradient(to bottom,  #cc0000 0%,#cc0000 100%);
filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#cc0000', endColorstr='#cc0000',GradientType=0 );

            }
            .center{
                color: #fff;
                text-align: center;
            }
            code{
                width: 500px;
                display: block;
                background: #fff;
                padding: 5px;
            }
            #code{
                width: 512px;
                padding: 6px;
                border: 1px dashed #fff;
                margin: auto;
            }
            .module{
                color: #febf01;
            }
        </style>
    </head>
    <body>
        <div class="center">
            <h1>Hello world!</h1>
            <h2>I am the <strong class="module">Welcome</strong> module</h2>
            <h1>and below is my source code..</h1>
        </div>
        
        <div id="code">
            <?php print highlight_file(__DIR__ . '/welcome.module.php', true); ?>
        </div>
        <h2 class="center">My sole purpose is to prevent a 404 error</h2>
    </body>
</html>