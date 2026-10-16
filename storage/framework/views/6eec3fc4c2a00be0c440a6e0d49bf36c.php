<div>
    <p class="muted" style="margin-bottom:.75rem"><?php echo e($meeting->title); ?></p>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($error): ?>
        <p style="color:var(--bad);padding:1rem 0"><?php echo e($error); ?></p>
    <?php elseif(empty($files)): ?>
        <p class="muted" style="padding:1rem 0"><?php echo e($message ?: 'No recording is available.'); ?></p>
    <?php else: ?>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $files; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $f): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
            <div class="rec-row">
                <div>
                    <b><?php echo e($f['label']); ?></b><br>
                    <span class="muted"><?php echo e($f['type']); ?> · <?php echo e(\Illuminate\Support\Number::fileSize($f['size'], 1)); ?></span>
                </div>
                <a class="btn btn-sm btn-primary" href="<?php echo e($f['url']); ?>" <?php if(empty($archived)): ?> target="_blank" rel="noopener noreferrer" <?php endif; ?> download>Download</a>
            </div>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(! empty($archived)): ?>
            <p class="muted" style="margin-top:.8rem">Recordings are stored in the organisation's Google Drive and download through this portal.</p>
        <?php else: ?>
            <p class="muted" style="margin-top:.8rem">Download links expire after about an hour. Reopen this window for a fresh link. Recordings are moved to Google Drive and removed from Zoom overnight.</p>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
</div>
<?php /**PATH C:\xampp\htdocs\ksum-meeting-portal\resources\views/livewire/meetings/recording-list.blade.php ENDPATH**/ ?>