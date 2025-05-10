<style>
  /* Main Layout and Typography */

/* Content Header Styles */
.content-header {
  margin-bottom: 24px;
  padding-bottom: 16px;
  border-bottom: 1px solid #e0e0e0;
}

.content-title {
  font-size: 24px;
  font-weight: 600;
  color: #1a3b6e;
}

/* Stats Summary Cards */
.stats-summary {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 20px;
  margin-bottom: 30px;
}

.summary-card {
  background-color: white;
  border-radius: 8px;
  padding: 20px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  text-align: center;
  border-top: 4px solid #1a73e8;
  transition: transform 0.2s;
}

.summary-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

.summary-card.urgent {
  border-top-color: #e53935;
}

.summary-value {
  font-size: 32px;
  font-weight: 700;
  color: #1a3b6e;
  margin-bottom: 8px;
}

.summary-card.urgent .summary-value {
  color: #e53935;
}

.summary-label {
  font-size: 14px;
  color: #666;
  font-weight: 500;
}

/* Search and Filters */
.search-filters {
  display: flex;
  justify-content: space-between;
  margin-bottom: 24px;
  flex-wrap: wrap;
  gap: 16px;
  background-color: white;
  padding: 16px;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.search-box {
  display: flex;
  min-width: 300px;
}

.search-box input {
  flex-grow: 1;
  padding: 10px 14px;
  border: 1px solid #ddd;
  border-radius: 4px 0 0 4px;
  font-size: 14px;
}

.search-btn {
  padding: 10px 14px;
  background-color: #1a73e8;
  color: white;
  border: none;
  border-radius: 0 4px 4px 0;
  cursor: pointer;
}

.filters {
  display: flex;
  gap: 12px;
  align-items: center;
}

.filters select {
  padding: 10px 14px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 14px;
  min-width: 180px;
  background-color: white;
}

.filter-btn {
  padding: 10px 20px;
  background-color: #1a73e8;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-weight: 500;
  transition: background-color 0.2s;
}

.filter-btn:hover {
  background-color: #1557b0;
}

/* Data Table Styles */
.data-table-container {
  background-color: white;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  margin-bottom: 24px;
  overflow-x: auto;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
}

.data-table th,
.data-table td {
  padding: 16px;
  text-align: left;
  border-bottom: 1px solid #eee;
}

.data-table th {
  background-color: #f8f9fa;
  font-weight: 600;
  color: #666;
  position: sticky;
  top: 0;
}

.data-table tbody tr:hover {
  background-color: #f9fbfd;
}

.urgent-row {
  background-color: #fff8f8;
}

.urgent-row:hover {
  background-color: #fff0f0;
}

/* Button Styles */
.view-docs-btn {
  padding: 6px 12px;
  background-color: #e8f0fe;
  color: #1a73e8;
  border: 1px solid #d2e3fc;
  border-radius: 4px;
  cursor: pointer;
  font-size: 13px;
  transition: background-color 0.2s;
}

.view-docs-btn:hover {
  background-color: #d2e3fc;
}

.warning-btn {
  padding: 6px 12px;
  background-color: #fff4e5;
  color: #f57c00;
  border: 1px solid #ffe0b2;
  border-radius: 4px;
  cursor: pointer;
  font-size: 13px;
}

/* Table Action Buttons */
.action-buttons {
  display: flex;
  gap: 8px;
}

.table-action {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  border: none;
  background-color: #f5f5f5;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
}

.approve-btn {
  background-color: #e6f7ed;
  color: #1e9d57;
}

.approve-btn:hover {
  background-color: #1e9d57;
  color: white;
}

.reject-btn {
  background-color: #feeef0;
  color: #d73343;
}

.reject-btn:hover {
  background-color: #d73343;
  color: white;
}

.message-btn {
  background-color: #e8f0fe;
  color: #1a73e8;
}

.message-btn:hover {
  background-color: #1a73e8;
  color: white;
}

.request-btn {
  background-color: #fff4e5;
  color: #f57c00;
}

.request-btn:hover {
  background-color: #f57c00;
  color: white;
}

/* Pagination */
.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  margin-top: 24px;
  gap: 16px;
}

.pagination-btn {
  padding: 8px 16px;
  border: 1px solid #ddd;
  background-color: white;
  border-radius: 4px;
  cursor: pointer;
  font-weight: 500;
  color: #555;
  transition: all 0.2s;
}

.pagination-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.pagination-btn:not(:disabled):hover {
  background-color: #f8f9fa;
}

.pagination-numbers {
  display: flex;
  align-items: center;
  gap: 8px;
}

.pagination-number {
  width: 36px;
  height: 36px;
  border: 1px solid #ddd;
  background-color: white;
  border-radius: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  font-weight: 500;
  transition: all 0.2s;
}

.pagination-number.active {
  background-color: #1a73e8;
  color: white;
  border-color: #1a73e8;
}

.pagination-number:not(.active):hover {
  background-color: #f8f9fa;
}

/* Responsive Design */
@media (max-width: 992px) {
  .search-filters {
    flex-direction: column;
  }
  
  .search-box {
    width: 100%;
  }
  
  .filters {
    width: 100%;
    flex-wrap: wrap;
  }
  
  .filters select {
    flex: 1;
    min-width: 140px;
  }
}

@media (max-width: 768px) {
  .stats-summary {
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  }
  
  .summary-value {
    font-size: 28px;
  }
  
  .summary-label {
    font-size: 13px;
  }
  
  .table-action {
    width: 32px;
    height: 32px;
  }
  
  .data-table th, 
  .data-table td {
    padding: 12px;
    font-size: 14px;
  }
}

@media (max-width: 576px) {
  .filters {
    flex-direction: column;
    width: 100%;
    gap: 10px;
  }
  
  .filters select,
  .filter-btn {
    width: 100%;
  }
  
  .data-table-container {
    overflow-x: auto;
  }
}
</style>
<div class="content-header">
  <h1 class="content-title">Approve New Registrations</h1>
</div>

<div class="stats-summary">
  <div class="summary-card">
    <div class="summary-value">15</div>
    <div class="summary-label">Pending Registrations</div>
  </div>
  <div class="summary-card">
    <div class="summary-value">8</div>
    <div class="summary-label">Pharmaceutical Companies</div>
  </div>
  <div class="summary-card">
    <div class="summary-value">7</div>
    <div class="summary-label">Medical Stores</div>
  </div>
  <div class="summary-card urgent">
    <div class="summary-value">3</div>
    <div class="summary-label">Waiting > 48 Hours</div>
  </div>
</div>

<div class="search-filters">
  <div class="search-box">
    <input type="text" placeholder="Search registrations...">
    <button class="search-btn">🔍</button>
  </div>
  <div class="filters">
    <select>
      <option>All User Types</option>
      <option>Pharmaceutical Companies</option>
      <option>Medical Stores</option>
    </select>
    <select>
      <option>All Registration Dates</option>
      <option>Today</option>
      <option>This Week</option>
      <option>This Month</option>
    </select>
    <button class="filter-btn">Filter</button>
  </div>
</div>

<div class="data-table-container">
  <table class="data-table">
    <thead>
      <tr>
        <th>Registration ID</th>
        <th>Business Name</th>
        <th>Type</th>
        <th>Registration Date</th>
        <th>Contact Person</th>
        <th>Documents</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>REG-0025</td>
        <td>MedCare Pharmacy</td>
        <td>Medical Store</td>
        <td>10 Feb 2025 (10:25 AM)</td>
        <td>John Smith</td>
        <td>
          <button class="view-docs-btn">View Documents</button>
        </td>
        <td>
          <div class="action-buttons">
            <button class="table-action approve-btn" title="Approve">✓</button>
            <button class="table-action reject-btn" title="Reject">✗</button>
            <button class="table-action message-btn" title="Send Message">💬</button>
          </div>
        </td>
      </tr>
      <tr class="urgent-row">
        <td>REG-0022</td>
        <td>BioTech Pharmaceuticals</td>
        <td>Pharmaceutical Company</td>
        <td>08 Feb 2025 (3:45 PM)</td>
        <td>Sarah Johnson</td>
        <td>
          <button class="view-docs-btn">View Documents</button>
        </td>
        <td>
          <div class="action-buttons">
            <button class="table-action approve-btn" title="Approve">✓</button>
            <button class="table-action reject-btn" title="Reject">✗</button>
            <button class="table-action message-btn" title="Send Message">💬</button>
          </div>
        </td>
      </tr>
      <tr>
        <td>REG-0024</td>
        <td>HealthPlus Drugstore</td>
        <td>Medical Store</td>
        <td>09 Feb 2025 (11:30 AM)</td>
        <td>Michael Brown</td>
        <td>
          <button class="warning-btn">Documents Missing</button>
        </td>
        <td>
          <div class="action-buttons">
            <button class="table-action request-btn" title="Request Documents">📑</button>
            <button class="table-action reject-btn" title="Reject">✗</button>
            <button class="table-action message-btn" title="Send Message">💬</button>
          </div>
        </td>
      </tr>
    </tbody>
  </table>
</div>

<div class="pagination">
  <button class="pagination-btn" disabled>Previous</button>
  <div class="pagination-numbers">
    <button class="pagination-number active">1</button>
    <button class="pagination-number">2</button>
  </div>
  <button class="pagination-btn">Next</button>
</div>