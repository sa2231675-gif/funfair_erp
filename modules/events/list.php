<?php
require_once '../../config/config.php';
requireLogin();

$pageTitle = "Upcoming Events";

$stmt = $pdo->query("SELECT * FROM events WHERE status = 'active' ORDER BY event_date ASC");
$events = $stmt->fetchAll();

include '../../includes/header.php';
?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mt-2 mb-2">
                <div class="col-sm-6">
                    <h1 class="text-glow"><i class="fas fa-calendar-star text-neon-danger mr-2"></i>Upcoming Events</h1>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <?php foreach ($events as $event): ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card card-dark card-outline card-danger h-100">
                        <div class="card-header border-bottom-0">
                            <h3 class="card-title text-glow w-100">
                                <i class="fas fa-calendar-day text-neon-danger mr-2"></i>
                                <?php echo htmlspecialchars($event['title']); ?>
                            </h3>
                        </div>
                        <div class="card-body pt-0 d-flex flex-column">
                            <p class="text-muted"><i class="far fa-clock mr-1"></i> <?php echo date('F d, Y', strtotime($event['event_date'])); ?></p>
                            <?php if(!empty($event['description'])): ?>
                                <p class="flex-grow-1" style="color: #cbd5e1;"><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
                            <?php endif; ?>
                            <ul class="list-group list-group-unbordered mt-auto table-dark-custom">
                                <li class="list-group-item" style="background: transparent; border-color: rgba(255,255,255,0.1); padding-left: 0; padding-right: 0;">
                                    <b>Ticket Price</b> <span class="float-right text-neon-success font-weight-bold" style="font-size: 1.1rem;">Rs. <?php echo number_format($event['price'], 2); ?></span>
                                </li>
                                <li class="list-group-item" style="background: transparent; border-color: rgba(255,255,255,0.1); padding-left: 0; padding-right: 0;">
                                    <b>Capacity</b> <span class="float-right text-neon-info"><?php echo $event['capacity']; ?> People</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($events)): ?>
                    <div class="col-12">
                        <div class="alert bg-dark text-glow-info mt-3" style="border-left: 5px solid #22d3ee;">
                            <h5><i class="icon fas fa-info-circle text-neon-info"></i> No Events Currently</h5>
                            There are no active upcoming events. Please check back later!
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
</div>

<?php include '../../includes/footer.php'; ?>
