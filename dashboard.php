<?php require_once __DIR__ . '/../includes/auth.php'; requireLogin(); require_once __DIR__ . '/../config/database.php'; /* |-------------------------------------------------------------------------- | DASHBOARD STATISTICS |-------------------------------------------------------------------------- */ $totalItems = 0; $availableItems = 0; $borrowedItems = 0; $overdueItems = 0; /* |-------------------------------------------------------------------------- | TOTAL ITEMS |-------------------------------------------------------------------------- */ $stmt = $pdo->query(" SELECT COUNT(*) FROM items "); $totalItems = (int) $stmt->fetchColumn(); /* |-------------------------------------------------------------------------- | AVAILABLE ITEMS |-------------------------------------------------------------------------- */ $stmt = $pdo->query(" SELECT COUNT(*) FROM items WHERE status = 'Available' "); $availableItems = (int) $stmt->fetchColumn(); /* |-------------------------------------------------------------------------- | BORROWED ITEMS |-------------------------------------------------------------------------- */ $stmt = $pdo->query(" SELECT COUNT(*) FROM items WHERE status = 'Borrowed' "); $borrowedItems = (int) $stmt->fetchColumn(); /* |-------------------------------------------------------------------------- | OVERDUE ITEMS |-------------------------------------------------------------------------- */ $stmt = $pdo->query(" SELECT COUNT(*) FROM transactions WHERE returned_date IS NULL AND due_date < CURDATE() "); $overdueItems = (int) $stmt->fetchColumn(); /* |-------------------------------------------------------------------------- | RECENT TRANSACTIONS |-------------------------------------------------------------------------- */ $recentTransactions = []; $stmt = $pdo->query(" SELECT t.transaction_code, i.item_name, b.full_name AS borrower_name, t.borrowed_date, t.status FROM transactions t LEFT JOIN items i ON t.item_id = i.id LEFT JOIN borrowers b ON t.borrower_id = b.id ORDER BY t.created_at DESC LIMIT 5 "); $recentTransactions = $stmt->fetchAll(PDO::FETCH_ASSOC); ?> <?php require_once __DIR__ . '/../includes/header.php'; ?> <div class="app-layout">
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>


<main class="main-content">


    <!-- PAGE HEADER -->

    <div class="page-header">

        <div>

            <span class="page-label">
                ADMIN PANEL
            </span>

            <h1>
                Dashboard
            </h1>

            <p>
                Welcome back,
                <strong>
                    <?= htmlspecialchars($_SESSION['full_name'] ?? 'Admin') ?>
                </strong>.
                Here's what's happening with your inventory today.
            </p>

        </div>


        <div class="dashboard-date">

            <span>
                📅
            </span>

            <div>

                <small>
                    Today
                </small>

                <strong>
                    <?= date('F d, Y') ?>
                </strong>

            </div>

        </div>

    </div>



    <!-- OVERVIEW -->

    <section class="dashboard-section">


        <div class="section-heading">

            <div>

                <h2>
                    Overview
                </h2>

                <p>
                    Current inventory and borrowing statistics
                </p>

            </div>

        </div>



        <div class="dashboard-cards">


            <!-- TOTAL ITEMS -->

            <div class="dashboard-card stat-card blue">

                <div class="stat-card-top">

                    <div>

                        <span class="stat-title">
                            Total Items
                        </span>

                        <div class="number">
                            <?= $totalItems ?>
                        </div>

                    </div>


                    <div class="stat-icon">
                        📦
                    </div>

                </div>


                <div class="stat-footer">
                    All inventory items
                </div>

            </div>



            <!-- AVAILABLE -->

            <div class="dashboard-card stat-card green">

                <div class="stat-card-top">

                    <div>

                        <span class="stat-title">
                            Available
                        </span>

                        <div class="number">
                            <?= $availableItems ?>
                        </div>

                    </div>


                    <div class="stat-icon">
                        ✓
                    </div>

                </div>


                <div class="stat-footer">
                    Ready to borrow
                </div>

            </div>



            <!-- BORROWED -->

            <div class="dashboard-card stat-card purple">

                <div class="stat-card-top">

                    <div>

                        <span class="stat-title">
                            Borrowed
                        </span>

                        <div class="number">
                            <?= $borrowedItems ?>
                        </div>

                    </div>


                    <div class="stat-icon">
                        ↗
                    </div>

                </div>


                <div class="stat-footer">
                    Currently borrowed
                </div>

            </div>



            <!-- OVERDUE -->

            <div class="dashboard-card stat-card orange">

                <div class="stat-card-top">

                    <div>

                        <span class="stat-title">
                            Overdue
                        </span>

                        <div class="number">
                            <?= $overdueItems ?>
                        </div>

                    </div>


                    <div class="stat-icon">
                        !
                    </div>

                </div>


                <div class="stat-footer">
                    Requires attention
                </div>

            </div>


        </div>

    </section>



    <!-- RECENT TRANSACTIONS -->

    <section class="dashboard-section">


        <div class="section-heading">

            <div>

                <h2>
                    Recent Transactions
                </h2>

                <p>
                    Latest inventory borrowing activity
                </p>

            </div>


            <a
                href="#"
                class="view-all-btn"
            >
                View All →
            </a>

        </div>



        <div class="table-container">


            <table class="data-table">


                <thead>

                    <tr>

                        <th>
                            Transaction
                        </th>

                        <th>
                            Item
                        </th>

                        <th>
                            Borrower
                        </th>

                        <th>
                            Date
                        </th>

                        <th>
                            Status
                        </th>

                    </tr>

                </thead>



                <tbody>


                <?php if (empty($recentTransactions)): ?>

                    <tr>

                        <td colspan="5">

                            <div class="empty-state">

                                <div class="empty-state-icon">
                                    📋
                                </div>

                                <h3>
                                    No transactions yet
                                </h3>

                                <p>
                                    Borrowing and return transactions
                                    will appear here.
                                </p>

                            </div>

                        </td>

                    </tr>


                <?php else: ?>


                    <?php foreach ($recentTransactions as $transaction): ?>

                        <tr>

                            <td>
                                <?= htmlspecialchars(
                                    $transaction['transaction_code']
                                ) ?>
                            </td>


                            <td>
                                <?= htmlspecialchars(
                                    $transaction['item_name'] ?? 'Unknown'
                                ) ?>
                            </td>


                            <td>
                                <?= htmlspecialchars(
                                    $transaction['borrower_name'] ?? 'Unknown'
                                ) ?>
                            </td>


                            <td>
                                <?= htmlspecialchars(
                                    $transaction['borrowed_date']
                                ) ?>
                            </td>


                            <td>

                                <span class="status status-borrowed">

                                    <?= htmlspecialchars(
                                        $transaction['status']
                                    ) ?>

                                </span>

                            </td>

                        </tr>

                    <?php endforeach; ?>


                <?php endif; ?>


                </tbody>

            </table>

        </div>

    </section>



    <!-- QUICK ACTIONS -->

    <section class="dashboard-section">


        <div class="section-heading">

            <div>

                <h2>
                    Quick Actions
                </h2>

                <p>
                    Common administrative tasks
                </p>

            </div>

        </div>



        <div class="quick-actions">


            <a
                href="#"
                class="quick-action"
            >

                <div class="quick-action-icon">
                    ＋
                </div>


                <div class="quick-action-text">

                    <strong>
                        Add Inventory
                    </strong>

                    <span>
                        Add a new item
                    </span>

                </div>


                <span class="quick-action-arrow">
                    →
                </span>

            </a>



            <a
                href="#"
                class="quick-action"
            >

                <div class="quick-action-icon">
                    ↗
                </div>


                <div class="quick-action-text">

                    <strong>
                        Borrow Item
                    </strong>

                    <span>
                        Record a new borrowing
                    </span>

                </div>


                <span class="quick-action-arrow">
                    →
                </span>

            </a>



            <a
                href="#"
                class="quick-action"
            >

                <div class="quick-action-icon">
                    ↩
                </div>


                <div class="quick-action-text">

                    <strong>
                        Process Return
                    </strong>

                    <span>
                        Record an item return
                    </span>

                </div>


                <span class="quick-action-arrow">
                    →
                </span>

            </a>


        </div>

    </section>


</main>

</div> <?php require_once __DIR__ . '/../includes/footer.php'; ?>