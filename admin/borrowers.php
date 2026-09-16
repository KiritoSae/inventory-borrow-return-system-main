<?php require_once __DIR__ . '/../includes/auth.php'; requireLogin(); require_once __DIR__ . '/../config/database.php'; $borrowers = []; $borrowerCount = 0; $departmentList = []; $editBorrower = null; // LOAD BORROWERS try { $stmt = $pdo->query(" SELECT b.id, b.borrower_code, b.full_name, b.department_id, b.position, b.contact_number, b.email, b.status, b.created_at, d.department_name FROM borrowers b LEFT JOIN departments d ON b.department_id = d.id ORDER BY b.full_name ASC "); $borrowers = $stmt->fetchAll(PDO::FETCH_ASSOC); $borrowerCount = count($borrowers); } catch (PDOException $e) { $borrowers = []; $borrowerCount = 0; } // LOAD DEPARTMENTS try { $stmt = $pdo->query(" SELECT id, department_name FROM departments WHERE status = 'Active' ORDER BY department_name ASC "); $departmentList = $stmt->fetchAll(PDO::FETCH_ASSOC); } catch (PDOException $e) { $departmentList = []; } // EDIT BORROWER if (isset($_GET['edit'])) { $editId = (int) $_GET['edit']; if ($editId > 0) { try { $stmt = $pdo->prepare(" SELECT id, borrower_code, full_name, department_id, position, contact_number, email, status FROM borrowers WHERE id = ? LIMIT 1 "); $stmt->execute([$editId]); $editBorrower = $stmt->fetch(PDO::FETCH_ASSOC) ?: null; } catch (PDOException $e) { $editBorrower = null; } } } ?> <?php require_once __DIR__ . '/../includes/header.php'; ?> <div class="app-layout"> <?php require_once __DIR__ . '/../includes/sidebar.php'; ?> <main class="main-content">
<div class="page-header">

    <div>

        <span class="borrower-label">
            BORROWER MANAGEMENT
        </span>

        <h1>Borrowers</h1>

        <p>
            Manage employees who can borrow company inventory items.
        </p>

    </div>

    <div class="borrower-count">

        <strong>
            <?= $borrowerCount ?>
        </strong>

        <span>
            Registered Borrowers
        </span>

    </div>

</div>


<?php if (isset($_GET['success'])): ?>

    <div class="borrower-alert success-alert">

        <?php if ($_GET['success'] === 'added'): ?>

            ✓ Borrower added successfully.

        <?php elseif ($_GET['success'] === 'updated'): ?>

            ✓ Borrower updated successfully.

        <?php elseif ($_GET['success'] === 'deleted'): ?>

            ✓ Borrower deleted successfully.

        <?php else: ?>

            ✓ Operation completed successfully.

        <?php endif; ?>

    </div>

<?php endif; ?>


<?php if (isset($_GET['error'])): ?>

    <div class="borrower-alert error-alert">

        <?php if ($_GET['error'] === 'duplicate'): ?>

            Borrower ID already exists.

        <?php elseif ($_GET['error'] === 'required'): ?>

            Please complete all required fields.

        <?php else: ?>

            Unable to complete the operation.

        <?php endif; ?>

    </div>

<?php endif; ?>


<!-- ADD / EDIT BORROWER -->

<div class="borrower-card">

    <div class="borrower-card-header">

        <h2>
            <?= $editBorrower ? 'Edit Borrower' : 'Add Borrower' ?>
        </h2>

        <p>
            <?= $editBorrower
                ? 'Update borrower information.'
                : 'Register a new employee who can borrow inventory.'
            ?>
        </p>

    </div>


    <form
        method="POST"
        action="../actions/<?= $editBorrower
            ? 'update_borrower.php'
            : 'save_borrower.php'
        ?>"
        class="borrower-form"
    >

        <?php if ($editBorrower): ?>

            <input
                type="hidden"
                name="id"
                value="<?= (int) $editBorrower['id'] ?>"
            >

        <?php endif; ?>


        <div class="borrower-grid">


            <div class="borrower-field">

                <label for="borrower_code">
                    Borrower ID *
                </label>

                <input
                    type="text"
                    id="borrower_code"
                    name="borrower_code"
                    class="form-control"
                    placeholder="Example: EMP-001"
                    value="<?= htmlspecialchars(
                        $editBorrower['borrower_code'] ?? ''
                    ) ?>"
                    required
                >

            </div>


            <div class="borrower-field">

                <label for="full_name">
                    Full Name *
                </label>

                <input
                    type="text"
                    id="full_name"
                    name="full_name"
                    class="form-control"
                    placeholder="Example: Juan Dela Cruz"
                    value="<?= htmlspecialchars(
                        $editBorrower['full_name'] ?? ''
                    ) ?>"
                    required
                >

            </div>


            <div class="borrower-field">

                <label for="department_id">
                    Department *
                </label>

                <select
                    id="department_id"
                    name="department_id"
                    class="form-control"
                    required
                >

                    <option value="">
                        Select department
                    </option>

                    <?php foreach ($departmentList as $department): ?>

                        <option
                            value="<?= (int) $department['id'] ?>"
                            <?= (
                                isset($editBorrower['department_id']) &&
                                (int) $editBorrower['department_id']
                                === (int) $department['id']
                            ) ? 'selected' : '' ?>
                        >

                            <?= htmlspecialchars(
                                $department['department_name']
                            ) ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <div class="borrower-field">

                <label for="position">
                    Position
                </label>

                <input
                    type="text"
                    id="position"
                    name="position"
                    class="form-control"
                    placeholder="Example: Staff"
                    value="<?= htmlspecialchars(
                        $editBorrower['position'] ?? ''
                    ) ?>"
                >

            </div>


            <div class="borrower-field">

                <label for="contact_number">
                    Contact Number
                </label>

                <input
                    type="text"
                    id="contact_number"
                    name="contact_number"
                    class="form-control"
                    placeholder="Optional"
                    value="<?= htmlspecialchars(
                        $editBorrower['contact_number'] ?? ''
                    ) ?>"
                >

            </div>


            <div class="borrower-field">

                <label for="email">
                    Email
                </label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    class="form-control"
                    placeholder="Optional"
                    value="<?= htmlspecialchars(
                        $editBorrower['email'] ?? ''
                    ) ?>"
                >

            </div>


            <?php if ($editBorrower): ?>

                <div class="borrower-field">

                    <label for="status">
                        Status
                    </label>

                    <select
                        id="status"
                        name="status"
                        class="form-control"
                    >

                        <option
                            value="Active"
                            <?= $editBorrower['status'] === 'Active'
                                ? 'selected'
                                : '' ?>
                        >
                            Active
                        </option>

                        <option
                            value="Inactive"
                            <?= $editBorrower['status'] === 'Inactive'
                                ? 'selected'
                                : '' ?>
                        >
                            Inactive
                        </option>

                    </select>

                </div>

            <?php endif; ?>


        </div>


        <div class="borrower-buttons">

            <?php if ($editBorrower): ?>

                <a
                    href="borrowers.php"
                    class="btn borrower-cancel"
                >
                    Cancel
                </a>

                <button
                    type="submit"
                    class="btn btn-primary borrower-save"
                >
                    Save Changes
                </button>

            <?php else: ?>

                <button
                    type="submit"
                    class="btn btn-primary borrower-save"
                >
                    + Add Borrower
                </button>

            <?php endif; ?>

        </div>

    </form>

</div>

<!-- BORROWER LIST -->

<div class="borrower-card">

    <div class="borrower-card-header">

        <h2>
            Borrower List
        </h2>

        <p>
            All registered employees who can borrow inventory.
        </p>

    </div>


    <div class="table-container">

        <table class="data-table">

            <thead>

                <tr>

                    <th>Borrower ID</th>
                    <th>Full Name</th>
                    <th>Department</th>
                    <th>Position</th>
                    <th>Contact</th>
                    <th>Status</th>
                    <th>Actions</th>

                </tr>

            </thead>


            <tbody>

            <?php if (empty($borrowers)): ?>

                <tr>

                    <td colspan="7">

                        <div class="borrower-empty">

                            <div class="borrower-empty-icon">
                                👥
                            </div>

                            <strong>
                                No borrowers yet
                            </strong>

                            <p>
                                Add your first borrower using the form above.
                            </p>

                        </div>

                    </td>

                </tr>

            <?php else: ?>

                <?php foreach ($borrowers as $borrower): ?>

                    <tr>

                        <td>

                            <strong class="borrower-code">

                                <?= htmlspecialchars(
                                    $borrower['borrower_code']
                                ) ?>

                            </strong>

                        </td>


                        <td>

                            <strong>

                                <?= htmlspecialchars(
                                    $borrower['full_name']
                                ) ?>

                            </strong>

                        </td>


                        <td>

                            <?= htmlspecialchars(
                                $borrower['department_name'] ?? '—'
                            ) ?>

                        </td>


                        <td>

                            <?= htmlspecialchars(
                                $borrower['position'] ?: '—'
                            ) ?>

                        </td>


                        <td>

                            <?= htmlspecialchars(
                                $borrower['contact_number'] ?: '—'
                            ) ?>

                        </td>


                        <td>

                            <?php if (
                                $borrower['status'] === 'Active'
                            ): ?>

                                <span class="status status-available">
                                    Active
                                </span>

                            <?php else: ?>

                                <span class="status status-maintenance">
                                    Inactive
                                </span>

                            <?php endif; ?>

                        </td>


                        <td>

                            <div class="borrower-actions">

                                <a
                                    href="borrowers.php?edit=<?= (int) $borrower['id'] ?>"
                                    class="borrower-edit"
                                >
                                    Edit
                                </a>

                                <a
                                    href="../actions/delete_borrower.php?id=<?= (int) $borrower['id'] ?>"
                                    class="borrower-delete"
                                    onclick="return confirm('Are you sure you want to delete this borrower?');"
                                >
                                    Delete
                                </a>

                            </div>

                        </td>

                    </tr>

                <?php endforeach; ?>

            <?php endif; ?>

            </tbody>

        </table>

    </div>

</div>

</main> </div> <style> /* ========================================== BORROWER PAGE ========================================== */ .borrower-label { display: block; color: #198754; font-size: 11px; font-weight: 700; letter-spacing: 1px; margin-bottom: 5px; } .borrower-count { background: #ffffff; border: 1px solid #e4ebe6; border-radius: 12px; padding: 12px 20px; min-width: 160px; text-align: center; } .borrower-count strong { display: block; color: #173c29; font-size: 26px; } .borrower-count span { display: block; color: #78847d; font-size: 12px; margin-top: 3px; } /* ALERTS */ .borrower-alert { padding: 13px 16px; border-radius: 10px; margin-bottom: 20px; font-size: 14px; font-weight: 600; } .success-alert { background: #d1e7dd; color: #0f5132; } .error-alert { background: #f8d7da; color: #842029; } /* MAIN CARD */ .borrower-card { background: #ffffff; border: 1px solid #e4ebe6; border-radius: 14px; margin-bottom: 25px; overflow: hidden; } .borrower-card-header { padding: 20px 22px; border-bottom: 1px solid #edf1ee; } .borrower-card-header h2 { color: #173c29; font-size: 18px; margin-bottom: 5px; } .borrower-card-header p { color: #78847d; font-size: 13px; } /* FORM */ .borrower-form { padding: 22px; } .borrower-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; } .borrower-field label { display: block; margin-bottom: 7px; color: #536158; font-size: 13px; font-weight: 600; } .borrower-buttons { display: flex; justify-content: flex-end; gap: 10px; margin-top: 22px; } .borrower-save { width: auto; } .borrower-cancel { background: #e9ecef; color: #41464b; text-decoration: none; } .borrower-cancel:hover { background: #dfe3e6; } /* TABLE */ .borrower-code { color: #198754; } .borrower-actions { display: flex; gap: 7px; } .borrower-actions a { text-decoration: none; padding: 6px 10px; border-radius: 7px; font-size: 12px; font-weight: 600; } .borrower-edit { background: #d1e7dd; color: #0f5132; } .borrower-edit:hover { background: #b9dfce; } .borrower-delete { background: #f8d7da; color: #842029; } .borrower-delete:hover { background: #f1bfc4; } /* EMPTY STATE */ .borrower-empty { text-align: center; padding: 40px 20px; } .borrower-empty-icon { font-size: 32px; margin-bottom: 10px; } .borrower-empty strong { display: block; color: #536158; font-size: 15px; margin-bottom: 5px; } .borrower-empty p { color: #78847d; font-size: 13px; } /* MOBILE */ @media (max-width: 900px) { .borrower-grid { grid-template-columns: repeat(2, 1fr); } } @media (max-width: 650px) { .borrower-count { margin-top: 12px; } .borrower-grid { grid-template-columns: 1fr; } .borrower-buttons { flex-direction: column; } .borrower-buttons .btn { width: 100%; text-align: center; } .borrower-actions { flex-direction: column; } } </style> <?php require_once __DIR__ . '/../includes/footer.php'; ?>
