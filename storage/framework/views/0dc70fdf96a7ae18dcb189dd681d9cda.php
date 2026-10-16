<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['subtitle' => null]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter((['subtitle' => null]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $h = now()->hour;
    $greeting = $h < 12 ? 'Good morning' : ($h < 17 ? 'Good afternoon' : 'Good evening');
    $first = \Illuminate\Support\Str::of(auth()->user()?->name ?? '')->before(' ')->toString() ?: 'there';
?>

<div class="hero">
    <div>
        <h1 class="ttl-xl"><?php echo e($greeting); ?>, <?php echo e($first); ?></h1>
        <p><?php echo e($subtitle); ?></p>
        <?php echo e($slot); ?>

    </div>
    <div class="hero-date"><?php echo e(now()->format('l, j F')); ?></div>
</div>
<?php /**PATH C:\xampp\htdocs\ksum-meeting-portal\resources\views/components/hero.blade.php ENDPATH**/ ?>