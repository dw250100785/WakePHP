Subject: Your account confirmation
From: {$domain} <no-reply@{$domain}>
MIME-Version: 1.0
Content-type: text/html; charset=utf-8

Good day!<br /><br />

You just signed up on {$domain|escape}, your confirmation code: {$code|escape}<br /><br />

Type it to the confirmation form or click <a href="http://{$domain|escape}/{$locale|escape}/account/confirm?email={$email|escape:'url'}&code={$code|escape:'url'}">the link</a>.<br /><br />

Your password is: {$password|escape}<br /><br /><br />


Best regards,<br /><br />

{$domain|escape}

