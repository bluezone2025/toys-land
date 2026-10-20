<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:s="http://www.sitemaps.org/schemas/sitemap/0.9"
    xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
<xsl:output method="html" encoding="UTF-8" indent="yes"/>
<xsl:template match="/">
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8"/>
    <meta name="robots" content="noindex"/>
    <title>خريطة الموقع - Sitemap</title>
    <style>
        body { font-family: Tahoma, Arial, sans-serif; background: #f8fafc; color: #1e293b; margin: 0; padding: 32px 16px; }
        .box { max-width: 1100px; margin: auto; background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; }
        h1 { margin: 0; padding: 20px 24px; background: #0f172a; color: #fff; font-size: 20px; border-bottom: 4px solid #0ea5e9; }
        p { margin: 0; padding: 12px 24px; color: #64748b; font-size: 13px; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th { background: #f1f5f9; text-align: right; padding: 10px 24px; }
        td { padding: 9px 24px; border-top: 1px solid #f1f5f9; }
        td.url { direction: ltr; text-align: left; }
        a { color: #0284c7; text-decoration: none; }
        tr:hover td { background: #f8fafc; }
    </style>
</head>
<body>
<div class="box">
    <h1>🧸 خريطة الموقع — Sitemap</h1>
    <p>عدد الصفحات: <b><xsl:value-of select="count(s:urlset/s:url)"/></b> — هذه الصفحة مخصصة لمحركات البحث مثل Google.</p>
    <table>
        <tr><th>#</th><th>الرابط</th><th>الصور</th><th>آخر تعديل</th><th>الأولوية</th></tr>
        <xsl:for-each select="s:urlset/s:url">
            <tr>
                <td><xsl:value-of select="position()"/></td>
                <td class="url"><a href="{s:loc}"><xsl:value-of select="s:loc"/></a></td>
                <td><xsl:value-of select="count(image:image)"/></td>
                <td><xsl:value-of select="substring(s:lastmod, 1, 10)"/></td>
                <td><xsl:value-of select="s:priority"/></td>
            </tr>
        </xsl:for-each>
    </table>
</div>
</body>
</html>
</xsl:template>
</xsl:stylesheet>
