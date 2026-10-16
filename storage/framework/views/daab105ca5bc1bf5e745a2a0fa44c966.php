<div style="font-family:Segoe UI,Arial,sans-serif;max-width:520px;color:#13202b">
    <h2 style="margin:0 0 8px"><?php echo e($meeting->title); ?></h2>
    <p><?php echo e(match ($kind) {
        'confirm' => 'Your Zoom meeting is scheduled.',
        'rescheduled' => 'Your Zoom meeting was moved to a new time. Please use the details below.',
        default => 'Reminder: your Zoom meeting starts soon.',
    }); ?></p>
    <p><b>When:</b> <?php echo e($meeting->start_time->format('D, j M, g:i A')); ?> – <?php echo e($meeting->end_time->format('g:i A')); ?> (<?php echo e($meeting->start_time->format('T')); ?>)</p>
    <p>
        <a href="<?php echo e($meeting->join_url); ?>" style="background:#0b78b8;color:#fff;padding:10px 18px;text-decoration:none;font-weight:bold;border-radius:6px">Join Zoom meeting</a>
    </p>
    <p style="word-break:break-all;color:#555"><?php echo e($meeting->join_url); ?></p>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($meeting->passcode): ?>
        <p><b>Passcode:</b> <?php echo e($meeting->passcode); ?></p>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    <p style="color:#777;font-size:12px">Host start link: open the portal and click Start on this meeting.</p>
</div>
<?php /**PATH C:\xampp\htdocs\ksum-meeting-portal\resources\views/mail/meeting.blade.php ENDPATH**/ ?>