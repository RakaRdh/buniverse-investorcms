<?php
/**
 * Shared Standard Pagination Partial
 * 
 * Variables required:
 * - $pagination (array): ['page' => int, 'totalPages' => int, 'totalItems' => int, 'perPage' => int]
 * - $baseUrl (string, optional): base path, defaults to current_url()
 * - $extraParams (array, optional): additional query params
 */

$currentPage = (int)($pagination['page'] ?? 1);
$totalPages = (int)($pagination['totalPages'] ?? 1);
$totalItems = (int)($pagination['totalItems'] ?? 0);
$perPage = (int)($pagination['perPage'] ?? 25);
$targetUrl = $baseUrl ?? current_url();

$queryParams = $_GET;
if (isset($extraParams) && is_array($extraParams)) {
    $queryParams = array_merge($queryParams, $extraParams);
}

$buildPageUrl = function($pageNum) use ($queryParams, $targetUrl) {
    $params = $queryParams;
    $params['page'] = $pageNum;
    return $targetUrl . '?' . http_build_query($params);
};

// Calculate smart pagination window (max 7 numbers with ellipsis if many pages)
$pagesToShow = [];
if ($totalPages <= 7) {
    for ($i = 1; $i <= $totalPages; $i++) {
        $pagesToShow[] = $i;
    }
} else {
    $pagesToShow[] = 1;
    $start = max(2, $currentPage - 2);
    $end = min($totalPages - 1, $currentPage + 2);

    if ($start > 2) {
        $pagesToShow[] = '...';
    }
    for ($i = $start; $i <= $end; $i++) {
        $pagesToShow[] = $i;
    }
    if ($end < $totalPages - 1) {
        $pagesToShow[] = '...';
    }
    $pagesToShow[] = $totalPages;
}
?>

<div class="flex flex-col sm:flex-row items-center justify-between border-t border-border px-4 py-3 gap-3 text-xs text-muted-foreground">
    <div>
        Showing page <strong class="text-foreground"><?= $currentPage ?></strong> of <strong class="text-foreground"><?= max(1, $totalPages) ?></strong>
        <?php if ($totalItems > 0): ?>
            <span class="text-muted-foreground/70 ml-1">(<?= number_format($totalItems) ?> total)</span>
        <?php endif; ?>
    </div>

    <?php if ($totalPages > 1): ?>
    <div class="flex items-center gap-1 flex-wrap justify-center">
        <!-- First Button -->
        <?php if ($currentPage > 1): ?>
            <a href="<?= $buildPageUrl(1) ?>" class="rounded-md border border-input bg-background px-2.5 py-1 font-medium text-foreground hover:bg-muted transition-colors" title="First Page">&laquo; First</a>
        <?php else: ?>
            <span class="rounded-md border border-input bg-background px-2.5 py-1 font-medium text-muted-foreground opacity-40 cursor-not-allowed">&laquo; First</span>
        <?php endif; ?>

        <!-- Previous Button -->
        <?php if ($currentPage > 1): ?>
            <a href="<?= $buildPageUrl($currentPage - 1) ?>" class="rounded-md border border-input bg-background px-2.5 py-1 font-medium text-foreground hover:bg-muted transition-colors">Previous</a>
        <?php else: ?>
            <span class="rounded-md border border-input bg-background px-2.5 py-1 font-medium text-muted-foreground opacity-40 cursor-not-allowed">Previous</span>
        <?php endif; ?>

        <!-- Number Buttons with Ellipsis -->
        <?php foreach ($pagesToShow as $p): ?>
            <?php if ($p === '...'): ?>
                <span class="px-2 py-1 text-muted-foreground select-none font-medium">&hellip;</span>
            <?php elseif ($p == $currentPage): ?>
                <span class="rounded-md bg-primary px-3 py-1 font-bold text-primary-foreground shadow-xs"><?= $p ?></span>
            <?php else: ?>
                <a href="<?= $buildPageUrl($p) ?>" class="rounded-md border border-input bg-background px-2.5 py-1 font-medium text-foreground hover:bg-muted transition-colors"><?= $p ?></a>
            <?php endif; ?>
        <?php endforeach; ?>

        <!-- Next Button -->
        <?php if ($currentPage < $totalPages): ?>
            <a href="<?= $buildPageUrl($currentPage + 1) ?>" class="rounded-md border border-input bg-background px-2.5 py-1 font-medium text-foreground hover:bg-muted transition-colors">Next</a>
        <?php else: ?>
            <span class="rounded-md border border-input bg-background px-2.5 py-1 font-medium text-muted-foreground opacity-40 cursor-not-allowed">Next</span>
        <?php endif; ?>

        <!-- Last Button -->
        <?php if ($currentPage < $totalPages): ?>
            <a href="<?= $buildPageUrl($totalPages) ?>" class="rounded-md border border-input bg-background px-2.5 py-1 font-medium text-foreground hover:bg-muted transition-colors" title="Last Page">Last &raquo;</a>
        <?php else: ?>
            <span class="rounded-md border border-input bg-background px-2.5 py-1 font-medium text-muted-foreground opacity-40 cursor-not-allowed">Last &raquo;</span>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
