<div>
    <?php if (isset($component)) { $__componentOriginal04f02f1e0f152287a127192de01fe241 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal04f02f1e0f152287a127192de01fe241 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.hero','data' => ['subtitle' => 'Pick a time and the portal finds a free Zoom account for you.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('hero'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['subtitle' => 'Pick a time and the portal finds a free Zoom account for you.']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('meetings:create')): ?>
            <button class="btn btn-light" type="button" wire:click="mountAction('schedule')">+ Schedule a meeting</button>
        <?php endif; ?>
     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal04f02f1e0f152287a127192de01fe241)): ?>
<?php $attributes = $__attributesOriginal04f02f1e0f152287a127192de01fe241; ?>
<?php unset($__attributesOriginal04f02f1e0f152287a127192de01fe241); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal04f02f1e0f152287a127192de01fe241)): ?>
<?php $component = $__componentOriginal04f02f1e0f152287a127192de01fe241; ?>
<?php unset($__componentOriginal04f02f1e0f152287a127192de01fe241); ?>
<?php endif; ?>

    
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($result): ?>
        <section style="margin-bottom:1.5rem">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($result['type'] === 'ok'): ?>
                <div class="result ok">
                    <h2 class="ttl">Meeting scheduled</h2>
                    <p><b><?php echo e($result['title']); ?></b><br><?php echo e($result['range']); ?></p>

                    <label class="lbl">Participants link (share this)</label>
                    <div class="copybox">
                        <input readonly value="<?php echo e($result['join_url']); ?>" onclick="this.select()">
                        <button class="btn" type="button" x-on:click="ptCopy(<?php echo \Illuminate\Support\Js::from($result['join_url'])->toHtml() ?>, 'Join link copied')">Copy</button>
                    </div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($result['passcode']): ?>
                        <p class="muted">Passcode: <b><?php echo e($result['passcode']); ?></b></p>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                    <div class="actions">
                        <a class="btn btn-primary" href="<?php echo e($result['start_url']); ?>" target="_blank" rel="noopener">Start as host</a>
                        <button class="btn" type="button" x-on:click="ptCopy(<?php echo \Illuminate\Support\Js::from($result['invite'])->toHtml() ?>, 'Invitation copied')">Copy invitation</button>
                    </div>
                    <p class="muted" style="margin-top:.8rem">Keep the host button for yourself. Only use it to start the meeting as the host.</p>
                </div>
            <?php elseif($result['type'] === 'unavailable'): ?>
                <div class="result warn">
                    <h2 class="ttl">Couldn't schedule that slot</h2>
                    <p><?php echo e($result['message']); ?></p>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(count($result['suggestions'])): ?>
                        <p class="muted"><b>Free slots nearby</b> (click one to book it):</p>
                        <div class="chips">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $result['suggestions']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                <button class="chip" type="button" wire:click="pickSlot('<?php echo e($s['iso']); ?>')" wire:loading.attr="disabled"><?php echo e($s['label']); ?></button>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        </div>
                    <?php else: ?>
                        <p class="muted">No free slots were found nearby. Try a different day or a shorter duration.</p>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <div class="actions">
                        <button class="btn" type="button" wire:click="mountAction('schedule')">Change the request</button>
                    </div>
                </div>
            <?php else: ?>
                <div class="result bad">
                    <h2 class="ttl">Something went wrong</h2>
                    <p><?php echo e($result['msg']); ?></p>
                    <div class="actions">
                        <button class="btn" type="button" wire:click="mountAction('schedule')">Change the request</button>
                    </div>
                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </section>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    
    <section class="card">
        <h2 class="ttl">My meetings</h2>

        <div class="tabs">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = ['upcoming' => 'Upcoming', 'past' => 'Past', 'other' => 'Cancelled / not scheduled']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                <button type="button" class="tab <?php echo e($filter === $key ? 'active' : ''); ?>" wire:click="$set('filter', '<?php echo e($key); ?>')"><?php echo e($label); ?></button>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
        </div>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $meetings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
            <div class="mtg" <?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::$currentLoop['key'] = 'mtg-'.e($m->id).''; ?>wire:key="mtg-<?php echo e($m->id); ?>">
                <div class="mtg-date"><small><?php echo e($m->start_time->format('M')); ?></small><?php echo e($m->start_time->format('j')); ?></div>
                <div class="mtg-main">
                    <b><?php echo e($m->title); ?></b>
                    <?php if (isset($component)) { $__componentOriginal28a259054317f0c9c7f17daeb7d7881f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal28a259054317f0c9c7f17daeb7d7881f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.meeting-badge','data' => ['meeting' => $m]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('meeting-badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['meeting' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($m)]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal28a259054317f0c9c7f17daeb7d7881f)): ?>
<?php $attributes = $__attributesOriginal28a259054317f0c9c7f17daeb7d7881f; ?>
<?php unset($__attributesOriginal28a259054317f0c9c7f17daeb7d7881f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal28a259054317f0c9c7f17daeb7d7881f)): ?>
<?php $component = $__componentOriginal28a259054317f0c9c7f17daeb7d7881f; ?>
<?php unset($__componentOriginal28a259054317f0c9c7f17daeb7d7881f); ?>
<?php endif; ?><br>
                    <span class="muted"><?php echo e($m->start_time->format('g:i A')); ?> – <?php echo e($m->end_time->format('g:i A')); ?><?php echo e($m->department ? ' · '.$m->department : ''); ?></span>
                </div>
                <div class="actions" style="margin:0">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($m->isLive()): ?>
                        <button class="btn btn-sm" type="button" x-on:click="ptCopy(<?php echo \Illuminate\Support\Js::from($m->join_url)->toHtml() ?>, 'Join link copied')">Copy join link</button>
                        <a class="btn btn-sm btn-primary" href="<?php echo e(route('meetings.start', $m)); ?>" target="_blank" rel="noopener">Start</a>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($m->start_time->isFuture()): ?>
                            <button class="btn btn-sm" type="button" wire:click="mountAction('rescheduleMeeting', { meeting: <?php echo e($m->id); ?> })">Reschedule</button>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <button class="btn btn-sm btn-danger" type="button" wire:click="mountAction('cancelMeeting', { meeting: <?php echo e($m->id); ?> })">Cancel</button>
                    <?php elseif($m->isFinished()): ?>
                        <button class="btn btn-sm btn-primary" type="button" wire:click="mountAction('recording', { meeting: <?php echo e($m->id); ?> })">Download recording</button>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            </div>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            <p class="muted" style="padding:1rem 0">Nothing here yet.</p>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </section>

    <?php if (isset($component)) { $__componentOriginal028e05680f6c5b1e293abd7fbe5f9758 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal028e05680f6c5b1e293abd7fbe5f9758 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament-actions::components.modals','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament-actions::modals'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal028e05680f6c5b1e293abd7fbe5f9758)): ?>
<?php $attributes = $__attributesOriginal028e05680f6c5b1e293abd7fbe5f9758; ?>
<?php unset($__attributesOriginal028e05680f6c5b1e293abd7fbe5f9758); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal028e05680f6c5b1e293abd7fbe5f9758)): ?>
<?php $component = $__componentOriginal028e05680f6c5b1e293abd7fbe5f9758; ?>
<?php unset($__componentOriginal028e05680f6c5b1e293abd7fbe5f9758); ?>
<?php endif; ?>
</div>
<?php /**PATH C:\xampp\htdocs\ksum-meeting-portal\resources\views/livewire/meetings/meetings-page.blade.php ENDPATH**/ ?>