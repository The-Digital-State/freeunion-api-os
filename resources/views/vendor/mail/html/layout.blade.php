<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
</head>
<body>
<div class="wrapper">
    <div class="page">
        <div class="inner">
            <div class="logo">
                <a href="{{ config('app.front_url') }}" target="_blank" rel="noopener">
                    <img src="https://freeunion.online/logo.jpg" alt="" />
                </a>
            </div>
            <div class="inner-content">
                {{ Illuminate\Mail\Markdown::parse($slot) }}
            </div>
        </div>
    </div>
</div>
</body>
</html>
