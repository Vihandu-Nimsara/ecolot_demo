<section class="form-card large-form">
    <h1>Create Collection Route</h1>

    <?php if (!empty($data['errors']['route'])): ?>
        <div class="alert alert-danger">
            <?php echo htmlspecialchars($data['errors']['route']); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($data['errors']['request_ids'])): ?>
        <div class="alert alert-danger">
            <?php echo htmlspecialchars($data['errors']['request_ids']); ?>
        </div>
    <?php endif; ?>

    <div class="info-box">
        <strong>Council:</strong>
        <?php echo htmlspecialchars($data['profile']->council_name); ?>
    </div>

    <?php if (empty($data['campaigns'])): ?>
        <div class="alert alert-danger">
            No open or draft monthly campaign found. Create a campaign first.
        </div>
    <?php endif; ?>

    <?php if (empty($data['approved_requests'])): ?>
        <div class="alert alert-danger">
            No approved unassigned requests are available for route planning.
        </div>
    <?php endif; ?>

    <form method="POST" action="<?php echo url('municipal-officer/store-route'); ?>">
        <div class="form-group">
            <label for="campaign_id">Monthly Campaign</label>

            <select id="campaign_id" name="campaign_id" required>
                <option value="">Select campaign</option>

                <?php foreach ($data['campaigns'] as $campaign): ?>
                    <option value="<?php echo htmlspecialchars($campaign->campaign_id); ?>"
                        <?php echo ((int)($data['old']['campaign_id'] ?? 0) === (int)$campaign->campaign_id) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($campaign->campaign_name . ' (' . $campaign->campaign_month . '/' . $campaign->campaign_year . ') - ' . $campaign->status); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <?php if (!empty($data['errors']['campaign_id'])): ?>
                <small class="error-text"><?php echo htmlspecialchars($data['errors']['campaign_id']); ?></small>
            <?php endif; ?>
        </div>

        <div class="grid-2">
            <div class="form-group">
                <label for="route_name">Route Name</label>

                <input
                    type="text"
                    id="route_name"
                    name="route_name"
                    value="<?php echo htmlspecialchars($data['old']['route_name'] ?? ''); ?>"
                    placeholder="Example: Colombo 01 - Morning Route"
                    required
                >

                <?php if (!empty($data['errors']['route_name'])): ?>
                    <small class="error-text"><?php echo htmlspecialchars($data['errors']['route_name']); ?></small>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="collection_date">Collection Date</label>

                <input
                    type="date"
                    id="collection_date"
                    name="collection_date"
                    value="<?php echo htmlspecialchars($data['old']['collection_date'] ?? ''); ?>"
                    required
                >

                <?php if (!empty($data['errors']['collection_date'])): ?>
                    <small class="error-text"><?php echo htmlspecialchars($data['errors']['collection_date']); ?></small>
                <?php endif; ?>
            </div>
        </div>

        <div class="form-group">
            <label for="area_id">Collection Area</label>

            <select id="area_id" name="area_id" required>
                <option value="">Select area</option>

                <?php foreach ($data['areas'] as $area): ?>
                    <option value="<?php echo htmlspecialchars($area->area_id); ?>"
                        <?php echo ((int)($data['old']['area_id'] ?? 0) === (int)$area->area_id) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($area->postal_code . ' - ' . $area->area_name); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <?php if (!empty($data['errors']['area_id'])): ?>
                <small class="error-text"><?php echo htmlspecialchars($data['errors']['area_id']); ?></small>
            <?php endif; ?>
        </div>

        <div class="grid-2">
            <div class="form-group">
                <label for="collector_id">Collector</label>

                <select id="collector_id" name="collector_id" required>
                    <option value="">Select collector</option>

                    <?php foreach ($data['collectors'] as $collector): ?>
                        <option value="<?php echo htmlspecialchars($collector->user_id); ?>"
                            <?php echo ((int)($data['old']['collector_id'] ?? 0) === (int)$collector->user_id) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($collector->full_name . ' - ' . $collector->availability_status); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <?php if (!empty($data['errors']['collector_id'])): ?>
                    <small class="error-text"><?php echo htmlspecialchars($data['errors']['collector_id']); ?></small>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="vehicle_id">Vehicle</label>

                <select id="vehicle_id" name="vehicle_id" required>
                    <option value="">Select vehicle</option>

                    <?php foreach ($data['vehicles'] as $vehicle): ?>
                        <option value="<?php echo htmlspecialchars($vehicle->vehicle_id); ?>"
                            <?php echo ((int)($data['old']['vehicle_id'] ?? 0) === (int)$vehicle->vehicle_id) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($vehicle->vehicle_no . ' - ' . $vehicle->vehicle_type . ' - ' . $vehicle->status); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <?php if (!empty($data['errors']['vehicle_id'])): ?>
                    <small class="error-text"><?php echo htmlspecialchars($data['errors']['vehicle_id']); ?></small>
                <?php endif; ?>
            </div>
        </div>

        <hr>

        <h2>Approved Requests</h2>

        <p class="muted">
            Select requests that match the same area and collection date. The system validates this again on the server.
        </p>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Select</th>
                        <th>Request ID</th>
                        <th>User</th>
                        <th>Area</th>
                        <th>Date</th>
                        <th>Items</th>
                        <th>Weight</th>
                        <th>Address</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($data['approved_requests'] as $request): ?>
                        <?php
                            $checked = in_array(
                                (string) $request->request_id,
                                array_map('strval', $data['old']['request_ids'] ?? []),
                                true
                            );
                        ?>

                        <tr
                            class="request-row"
                            data-area-id="<?php echo htmlspecialchars($request->area_id); ?>"
                            data-date="<?php echo htmlspecialchars($request->collection_date); ?>"
                        >
                            <td>
                                <input
                                    type="checkbox"
                                    name="request_ids[]"
                                    value="<?php echo htmlspecialchars($request->request_id); ?>"
                                    <?php echo $checked ? 'checked' : ''; ?>
                                >
                            </td>

                            <td>#<?php echo htmlspecialchars($request->request_id); ?></td>

                            <td><?php echo htmlspecialchars($request->public_user_name); ?></td>

                            <td>
                                <?php echo htmlspecialchars($request->postal_code . ' - ' . $request->area_name); ?>
                            </td>

                            <td><?php echo htmlspecialchars($request->collection_date); ?></td>

                            <td><?php echo htmlspecialchars($request->item_count); ?></td>

                            <td>
                                <?php echo htmlspecialchars(number_format((float)$request->total_estimated_weight, 2)); ?> kg
                            </td>

                            <td><?php echo htmlspecialchars($request->pickup_address); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="button-row">
            <button
                type="submit"
                class="btn"
                <?php echo empty($data['campaigns']) || empty($data['approved_requests']) ? 'disabled' : ''; ?>
            >
                Create Route
            </button>

            <a class="btn secondary" href="<?php echo url('municipal-officer/routes'); ?>">
                Cancel
            </a>
        </div>
    </form>
</section>

<script>
    const areaSelect = document.getElementById('area_id');
    const dateInput = document.getElementById('collection_date');

    function filterRequestRows() {
        const selectedArea = areaSelect.value;
        const selectedDate = dateInput.value;

        document.querySelectorAll('.request-row').forEach(row => {
            const rowArea = row.dataset.areaId;
            const rowDate = row.dataset.date;
            const checkbox = row.querySelector('input[type="checkbox"]');

            const areaMatches = selectedArea === '' || rowArea === selectedArea;
            const dateMatches = selectedDate === '' || rowDate === selectedDate;

            if (areaMatches && dateMatches) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
                checkbox.checked = false;
            }
        });
    }

    areaSelect.addEventListener('change', filterRequestRows);
    dateInput.addEventListener('change', filterRequestRows);
    filterRequestRows();
</script>