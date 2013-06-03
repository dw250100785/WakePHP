{if $locale == 'ru'}Subject: [{$domain|escape}] Подтверждение учетной записи
From: {$domain} <no-reply@{$domain}>
MIME-Version: 1.0
Content-type: text/html; charset=utf-8
\
\
Добрый день!<br /><br />

Вы зарегистрировались на {$domain|escape}, Ваш код подтверждения: {$code|escape}<br /><br />

Введите его в форму подтверждения или нажмите на <a href="http://{$domain|escape}/{$locale|escape}/account/finishSignup?email={$email|escape:'url'}&code={$code|escape:'url'}">ссылку</a>.<br /><br />


С наилучшими пожеланиями,<br /><br />

Команда {$domain|escape}

{else}Subject: [{$domain|escape}] Your account confirmation
From: {$domain} <no-reply@{$domain}>
MIME-Version: 1.0
Content-type: text/html; charset=utf-8
\
\
Good day!<br /><br />

You just signed up on {$domain|escape}, your confirmation code: {$code|escape}<br /><br />

Type it to the confirmation form or click <a href="http://{$domain|escape}/{$locale|escape}/account/finishSignup?email={$email|escape:'url'}&code={$code|escape:'url'}">the link</a>.<br /><br />


Best regards,<br /><br />

{$domain|escape} team

{/if}