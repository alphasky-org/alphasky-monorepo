<!DOCTYPE html>
<html {!! Theme::htmlAttributes() !!}>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
{!! Theme::header() !!}
<style>body{margin:0}.website-design-canvas{width:100%;max-width:none;margin:0}.website-design-canvas>.ck-content{width:100%;max-width:none}a:focus-visible,button:focus-visible{outline:2px solid currentColor;outline-offset:4px}</style>
</head>
<body {!! Theme::bodyAttributes() !!}>
{!! apply_filters(THEME_FRONT_BODY, null) !!}
<main class="website-design-canvas">{!! Theme::content() !!}</main>
{!! Theme::footer() !!}
</body>
</html>
