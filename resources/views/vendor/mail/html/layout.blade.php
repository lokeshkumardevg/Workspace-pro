<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="color-scheme" content="light">
<meta name="supported-color-schemes" content="light">
<style>
/* We inline most CSS in the components, but standard resets remain here */
body, html {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif !important;
    background-color: #f4f7f6;
    margin: 0;
    padding: 0;
    width: 100% !important;
    -webkit-text-size-adjust: none;
}
.body {
    background-color: #f4f7f6;
    margin: 0;
    padding: 0;
    width: 100%;
}
.inner-body {
    background: #ffffff;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.05);
    margin: 30px auto;
    width: 600px;
    border: 1px solid #eaebed;
    overflow: hidden;
}
.header {
    background: linear-gradient(135deg, #4f46e5 0%, #312e81 100%);
    padding: 35px 40px;
    text-align: left;
}
.header a {
    color: #ffffff;
    font-size: 24px;
    font-weight: 900;
    text-decoration: none;
    letter-spacing: 0.05em;
    display: inline-block;
}
.header a span {
    color: #a5b4fc; /* light indigo */
}
.content-cell {
    padding: 40px 40px;
}
.footer {
    padding: 20px 40px;
    text-align: center;
    background-color: #f9fafb;
    border-top: 1px solid #eaebed;
}
.footer p {
    color: #9ca3af;
    font-size: 13px;
    line-height: 1.5;
    margin: 0;
}
</style>
</head>
<body class="body">
    <table class="wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background-color: #f4f7f6; margin: 0; padding: 0; width: 100%;">
        <tr>
            <td align="center">
                <table class="content" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                    {{ $header ?? '' }}

                    <!-- Email Body -->
                    <tr>
                        <td align="center" style="padding: 20px 0;">
                            <table class="inner-body" width="600" cellpadding="0" cellspacing="0" role="presentation" style="background: #ffffff; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); margin: 0 auto; width: 600px; border: 1px solid #eaebed; overflow: hidden;">
                                
                                <!-- CUSTOM HEADER INJECTED HERE TO STAY INSIDE THE WHITE CARD -->
                                <tr>
                                    <td class="header" style="background: linear-gradient(135deg, #4f46e5 0%, #312e81 100%); padding: 35px 40px; text-align: left;">
                                        <a href="{{ config('app.url') }}" style="color: #ffffff; font-size: 24px; font-weight: 900; text-decoration: none; letter-spacing: 0.05em; display: inline-block;">
                                            WHEEDLE <span style="color: #a5b4fc;">360</span>
                                        </a>
                                        <p style="color: #c7d2fe; font-size: 13px; margin: 5px 0 0 0; font-weight: 600; text-transform: uppercase; letter-spacing: 0.1em;">Automated System Alert</p>
                                    </td>
                                </tr>

                                <tr>
                                    <td class="content-cell" style="padding: 40px 40px;">
                                        {{ Illuminate\Mail\Markdown::parse($slot) }}

                                        {{ $subcopy ?? '' }}
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{ $footer ?? '' }}
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
