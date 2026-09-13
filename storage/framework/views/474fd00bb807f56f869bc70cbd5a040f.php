<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Arus Kas</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #1f2a37; }
        h1 { font-size: 16px; text-align: center; margin: 0 0 6px; text-transform: uppercase; }
        .meta { text-align: center; color: #555; font-size: 9px; margin-bottom: 18px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 7px 8px; }
        th { background: #e9e9e9; text-align: left; }
        .right { text-align: right; white-space: nowrap; }
        .in { color: #148a63; }
        .out { color: #c0392b; }
        .total-row td { font-weight: bold; background: #f5f5f5; }
        .company { text-align: center; margin-bottom: 10px; font-size: 10px; color: #555; }
    </style>
</head>
<body>
    <p class="company"><?php echo e($companyLabel ?? config('app.name')); ?></p>
    <h1>Laporan Arus Kas</h1>
    <div class="meta">
        Dicetak pada: <?php echo e($generatedDate); ?><br>
        Periode:
        <?php echo e(\Carbon\Carbon::parse($filterStartDate)->format('d M Y')); ?>

        –
        <?php echo e(\Carbon\Carbon::parse($filterEndDate)->format('d M Y')); ?>

    </div>

    <table>
        <thead>
            <tr>
                <th>Kategori</th>
                <th class="right">Nominal</th>
            </tr>
        </thead>
        <tbody>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $label => $amount): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                <?php $isIn = \Illuminate\Support\Str::contains(mb_strtolower((string) $label), 'masuk'); ?>
                <tr>
                    <td><?php echo e($label); ?></td>
                    <td class="right <?php echo e($isIn ? 'in' : 'out'); ?>">Rp <?php echo e(number_format((int) $amount, 0, ',', '.')); ?></td>
                </tr>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            <tr class="total-row">
                <td>Total Masuk</td>
                <td class="right in">Rp <?php echo e(number_format((int) $totalIn, 0, ',', '.')); ?></td>
            </tr>
            <tr class="total-row">
                <td>Total Keluar</td>
                <td class="right out">Rp <?php echo e(number_format((int) $totalOut, 0, ',', '.')); ?></td>
            </tr>
            <tr class="total-row">
                <td>Kas Bersih</td>
                <td class="right <?php echo e($net >= 0 ? 'in' : 'out'); ?>">Rp <?php echo e(number_format((int) $net, 0, ',', '.')); ?></td>
            </tr>
        </tbody>
    </table>
</body>
</html>
<?php /**PATH /Applications/XAMPP/xamppfiles/htdocs/application/wofins/resources/views/pdf/cash_flow_report.blade.php ENDPATH**/ ?>