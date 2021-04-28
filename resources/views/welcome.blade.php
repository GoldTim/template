<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Laravel</title>

        <!-- Fonts -->
        <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
        <style>
            body {
                font-family: 'Nunito', sans-serif;
            }
        </style>
    </head>
    <body class="antialiased">
    <form method="post" action="/order/pay" id="form">
        {!! csrf_field() !!}
        <input type="hidden" name="orderSn" value="Msn2021040610098102"/>
        <input type="hidden" name="payMethod" value="8"/>
        <label for="input"></label>
        <input id="input" name="code">
    </form>
    </body>
<script src="{{mix("js/app.js")}}"></script>
<script>
    document.getElementById('input').onchange=function () {
        document.getElementById('form').submit();
    }
</script>
</html>
