<?php $message = $message ?? ''; ?>
<h1>Something went wrong</h1>
<p>Our server hit an unexpected error. The team has been notified.</p>
<?php if (($config['app']['env'] ?? 'production') === 'development' && $message): ?>
    <pre><?= $e($message) ?></pre>
<?php endif; ?>
