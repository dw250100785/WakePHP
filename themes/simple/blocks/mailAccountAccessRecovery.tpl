{if $locale == 'ru'}Subject: [{$domain|escape}] Восстановление доступа к учетной записи
From: {$domain} <no-reply@{$domain}>
MIME-Version: 1.0
Content-type: text/html; charset=utf-8

Добрый день!<br /><br />

Вы запросили восстановление доступа к учетной записи на {$domain|escape}, Ваш код подтверждения: {$code|escape}<br /><br />

Введите его в форму подтверждения или нажмите на <a href="http://{$domain|escape}/{$locale|escape}/account/recovery?email={$email|escape:'url'}&code={$code|escape:'url'}">ссылку</a>.<br /><br />

Внимание! Ваш пароль будет изменен на: {$password|escape}<br /><br /><br />


С наилучшими пожеланиями,<br /><br />

Команда {$domain|escape}

{else}Subject: [{$domain|escape}] Account access recovery
From: {$domain} <no-reply@{$domain}>
MIME-Version: 1.0
Content-type: text/html; charset=utf-8

Good day!<br /><br />

You just requested account access recovery ({$domain|escape}), your confirmation code: {$code|escape}<br /><br />

Type it to the confirmation form or click <a href="http://{$domain|escape}/{$locale|escape}/account/recovery?email={$email|escape:'url'}&code={$code|escape:'url'}">the link</a>.<br /><br />

Attention! If you succeed your password is: {$password|escape}<br /><br /><br />


Best regards,<br /><br />

{$domain|escape} team

{/if}