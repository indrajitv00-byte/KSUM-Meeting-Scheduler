<?php
    $cal = '<rect x="3.5" y="5" width="17" height="15" rx="2.5"/><path d="M3.5 10h17M8 3v4M16 3v4"/>';
    $grid = '<rect x="4" y="4" width="6.5" height="6.5" rx="1.5"/><rect x="13.5" y="4" width="6.5" height="6.5" rx="1.5"/><rect x="4" y="13.5" width="6.5" height="6.5" rx="1.5"/><rect x="13.5" y="13.5" width="6.5" height="6.5" rx="1.5"/>';
    $monitor = '<rect x="3" y="5" width="18" height="12" rx="2"/><path d="M8 21h8M12 17v4"/>';
    $clock = '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>';
    $list = '<path d="M8 6h12M8 12h12M8 18h12M4 6h.01M4 12h.01M4 18h.01"/>';
    $rec = '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="3.2"/>';
    $users = '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c.6-3.4 3.2-5.5 6.5-5.5s5.9 2.1 6.5 5.5M17 4.8a3.5 3.5 0 0 1 0 6.4M18.5 14.7c1.7.7 3 2.4 3.5 5.3"/>';
    $user = '<circle cx="12" cy="8" r="4"/><path d="M4.5 20c.9-3.8 3.7-6 7.5-6s6.6 2.2 7.5 6"/>';
    $shield = '<path d="M12 3l8 3v6c0 4.5-3.2 7.8-8 9-4.8-1.2-8-4.5-8-9V6z"/>';
?>

<nav class="nav">
    <?php if (isset($component)) { $__componentOriginal5893a4ae82cbde8a6e1ba16203c33ac0 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5893a4ae82cbde8a6e1ba16203c33ac0 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.menu','data' => ['label' => 'Book a meeting','route' => 'meetings','permissions' => 'meetings:view','icon' => $cal]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('menu'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Book a meeting','route' => 'meetings','permissions' => 'meetings:view','icon' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($cal)]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5893a4ae82cbde8a6e1ba16203c33ac0)): ?>
<?php $attributes = $__attributesOriginal5893a4ae82cbde8a6e1ba16203c33ac0; ?>
<?php unset($__attributesOriginal5893a4ae82cbde8a6e1ba16203c33ac0); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5893a4ae82cbde8a6e1ba16203c33ac0)): ?>
<?php $component = $__componentOriginal5893a4ae82cbde8a6e1ba16203c33ac0; ?>
<?php unset($__componentOriginal5893a4ae82cbde8a6e1ba16203c33ac0); ?>
<?php endif; ?>
    <?php if (isset($component)) { $__componentOriginal5893a4ae82cbde8a6e1ba16203c33ac0 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5893a4ae82cbde8a6e1ba16203c33ac0 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.menu','data' => ['label' => 'My profile','route' => 'profile','icon' => $user]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('menu'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'My profile','route' => 'profile','icon' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($user)]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5893a4ae82cbde8a6e1ba16203c33ac0)): ?>
<?php $attributes = $__attributesOriginal5893a4ae82cbde8a6e1ba16203c33ac0; ?>
<?php unset($__attributesOriginal5893a4ae82cbde8a6e1ba16203c33ac0); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5893a4ae82cbde8a6e1ba16203c33ac0)): ?>
<?php $component = $__componentOriginal5893a4ae82cbde8a6e1ba16203c33ac0; ?>
<?php unset($__componentOriginal5893a4ae82cbde8a6e1ba16203c33ac0); ?>
<?php endif; ?>

    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any(['admin:dashboard:view', 'admin:zoom-accounts:view', 'admin:meetings:view', 'admin:users:view', 'admin:roles:view'])): ?>
        <div class="nav-title">Admin</div>
    <?php endif; ?>

    <?php if (isset($component)) { $__componentOriginal5893a4ae82cbde8a6e1ba16203c33ac0 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5893a4ae82cbde8a6e1ba16203c33ac0 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.menu','data' => ['label' => 'Overview','route' => 'admin.overview','permissions' => 'admin:dashboard:view','icon' => $grid]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('menu'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Overview','route' => 'admin.overview','permissions' => 'admin:dashboard:view','icon' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($grid)]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5893a4ae82cbde8a6e1ba16203c33ac0)): ?>
<?php $attributes = $__attributesOriginal5893a4ae82cbde8a6e1ba16203c33ac0; ?>
<?php unset($__attributesOriginal5893a4ae82cbde8a6e1ba16203c33ac0); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5893a4ae82cbde8a6e1ba16203c33ac0)): ?>
<?php $component = $__componentOriginal5893a4ae82cbde8a6e1ba16203c33ac0; ?>
<?php unset($__componentOriginal5893a4ae82cbde8a6e1ba16203c33ac0); ?>
<?php endif; ?>
    <?php if (isset($component)) { $__componentOriginal5893a4ae82cbde8a6e1ba16203c33ac0 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5893a4ae82cbde8a6e1ba16203c33ac0 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.menu','data' => ['label' => 'Zoom accounts','route' => 'admin.zoom-accounts','permissions' => 'admin:zoom-accounts:view','icon' => $monitor]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('menu'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Zoom accounts','route' => 'admin.zoom-accounts','permissions' => 'admin:zoom-accounts:view','icon' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($monitor)]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5893a4ae82cbde8a6e1ba16203c33ac0)): ?>
<?php $attributes = $__attributesOriginal5893a4ae82cbde8a6e1ba16203c33ac0; ?>
<?php unset($__attributesOriginal5893a4ae82cbde8a6e1ba16203c33ac0); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5893a4ae82cbde8a6e1ba16203c33ac0)): ?>
<?php $component = $__componentOriginal5893a4ae82cbde8a6e1ba16203c33ac0; ?>
<?php unset($__componentOriginal5893a4ae82cbde8a6e1ba16203c33ac0); ?>
<?php endif; ?>
    <?php if (isset($component)) { $__componentOriginal5893a4ae82cbde8a6e1ba16203c33ac0 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5893a4ae82cbde8a6e1ba16203c33ac0 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.menu','data' => ['label' => 'Day schedule','route' => 'admin.schedule','permissions' => 'admin:meetings:view','icon' => $clock]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('menu'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Day schedule','route' => 'admin.schedule','permissions' => 'admin:meetings:view','icon' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($clock)]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5893a4ae82cbde8a6e1ba16203c33ac0)): ?>
<?php $attributes = $__attributesOriginal5893a4ae82cbde8a6e1ba16203c33ac0; ?>
<?php unset($__attributesOriginal5893a4ae82cbde8a6e1ba16203c33ac0); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5893a4ae82cbde8a6e1ba16203c33ac0)): ?>
<?php $component = $__componentOriginal5893a4ae82cbde8a6e1ba16203c33ac0; ?>
<?php unset($__componentOriginal5893a4ae82cbde8a6e1ba16203c33ac0); ?>
<?php endif; ?>
    <?php if (isset($component)) { $__componentOriginal5893a4ae82cbde8a6e1ba16203c33ac0 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5893a4ae82cbde8a6e1ba16203c33ac0 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.menu','data' => ['label' => 'All meetings','route' => 'admin.meetings','permissions' => 'admin:meetings:view','icon' => $list]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('menu'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'All meetings','route' => 'admin.meetings','permissions' => 'admin:meetings:view','icon' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($list)]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5893a4ae82cbde8a6e1ba16203c33ac0)): ?>
<?php $attributes = $__attributesOriginal5893a4ae82cbde8a6e1ba16203c33ac0; ?>
<?php unset($__attributesOriginal5893a4ae82cbde8a6e1ba16203c33ac0); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5893a4ae82cbde8a6e1ba16203c33ac0)): ?>
<?php $component = $__componentOriginal5893a4ae82cbde8a6e1ba16203c33ac0; ?>
<?php unset($__componentOriginal5893a4ae82cbde8a6e1ba16203c33ac0); ?>
<?php endif; ?>
    <?php if (isset($component)) { $__componentOriginal5893a4ae82cbde8a6e1ba16203c33ac0 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5893a4ae82cbde8a6e1ba16203c33ac0 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.menu','data' => ['label' => 'Recordings','route' => 'admin.recordings','permissions' => 'admin:meetings:view','icon' => $rec]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('menu'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Recordings','route' => 'admin.recordings','permissions' => 'admin:meetings:view','icon' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($rec)]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5893a4ae82cbde8a6e1ba16203c33ac0)): ?>
<?php $attributes = $__attributesOriginal5893a4ae82cbde8a6e1ba16203c33ac0; ?>
<?php unset($__attributesOriginal5893a4ae82cbde8a6e1ba16203c33ac0); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5893a4ae82cbde8a6e1ba16203c33ac0)): ?>
<?php $component = $__componentOriginal5893a4ae82cbde8a6e1ba16203c33ac0; ?>
<?php unset($__componentOriginal5893a4ae82cbde8a6e1ba16203c33ac0); ?>
<?php endif; ?>
    <?php if (isset($component)) { $__componentOriginal5893a4ae82cbde8a6e1ba16203c33ac0 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5893a4ae82cbde8a6e1ba16203c33ac0 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.menu','data' => ['label' => 'Users','route' => 'admin.users','permissions' => 'admin:users:view','icon' => $users]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('menu'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Users','route' => 'admin.users','permissions' => 'admin:users:view','icon' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($users)]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5893a4ae82cbde8a6e1ba16203c33ac0)): ?>
<?php $attributes = $__attributesOriginal5893a4ae82cbde8a6e1ba16203c33ac0; ?>
<?php unset($__attributesOriginal5893a4ae82cbde8a6e1ba16203c33ac0); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5893a4ae82cbde8a6e1ba16203c33ac0)): ?>
<?php $component = $__componentOriginal5893a4ae82cbde8a6e1ba16203c33ac0; ?>
<?php unset($__componentOriginal5893a4ae82cbde8a6e1ba16203c33ac0); ?>
<?php endif; ?>
    <?php if (isset($component)) { $__componentOriginal5893a4ae82cbde8a6e1ba16203c33ac0 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5893a4ae82cbde8a6e1ba16203c33ac0 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.menu','data' => ['label' => 'Roles','route' => 'admin.roles','permissions' => 'admin:roles:view','icon' => $shield]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('menu'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Roles','route' => 'admin.roles','permissions' => 'admin:roles:view','icon' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($shield)]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5893a4ae82cbde8a6e1ba16203c33ac0)): ?>
<?php $attributes = $__attributesOriginal5893a4ae82cbde8a6e1ba16203c33ac0; ?>
<?php unset($__attributesOriginal5893a4ae82cbde8a6e1ba16203c33ac0); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5893a4ae82cbde8a6e1ba16203c33ac0)): ?>
<?php $component = $__componentOriginal5893a4ae82cbde8a6e1ba16203c33ac0; ?>
<?php unset($__componentOriginal5893a4ae82cbde8a6e1ba16203c33ac0); ?>
<?php endif; ?>
</nav>
<?php /**PATH C:\xampp\htdocs\ksum-meeting-portal\resources\views/components/sidebar.blade.php ENDPATH**/ ?>