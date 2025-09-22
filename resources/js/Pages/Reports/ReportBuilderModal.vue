<template>
  <modal :show="modelValue" @close="closeModal" size="xl">
    <template #header>
      <h5 class="modal-title">Report Builder</h5>
      <button type="button" class="btn-close" @click="closeModal" aria-label="Close"></button>
    </template>

    <div class="report-builder">
      <div class="row">
        <!-- Report Type Selection -->
        <div class="col-md-4">
          <div class="card h-100">
            <div class="card-header">
              <h6 class="mb-0">Report Types</h6>
            </div>
            <div class="list-group list-group-flush">
              <button 
                v-for="type in reportTypes" 
                :key="type.id"
                @click="selectReportType(type)"
                :class="['list-group-item list-group-item-action', selectedType?.id === type.id ? 'active' : '']"
              >
                <div class="d-flex w-100 justify-content-between">
                  <h6 class="mb-1">{{ type.name }}</h6>
                </div>
                <p class="mb-1 small">{{ type.description }}</p>
              </button>
            </div>
          </div>
        </div>

        <!-- Report Configuration -->
        <div class="col-md-8">
          <div class="card h-100">
            <div class="card-header">
              <h6 class="mb-0">Configure Report</h6>
            </div>
            <div class="card-body">
              <div v-if="!selectedType" class="text-center py-5">
                <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                <p class="mb-0">Select a report type to begin</p>
              </div>
              
              <form v-else @submit.prevent="generateReport">
                <!-- Report Name -->
                <div class="mb-4">
                  <label for="reportName" class="form-label">Report Name</label>
                  <input 
                    type="text" 
                    class="form-control" 
                    id="reportName" 
                    v-model="reportName"
                    required
                    placeholder="e.g., Weekly Attendance Report"
                  >
                </div>

                <!-- Date Range -->
                <div class="row mb-4">
                  <div class="col-md-6">
                    <label for="startDate" class="form-label">Start Date</label>
                    <input 
                      type="date" 
                      class="form-control" 
                      id="startDate" 
                      v-model="dateRange.start"
                      required
                    >
                  </div>
                  <div class="col-md-6">
                    <label for="endDate" class="form-label">End Date</label>
                    <input 
                      type="date" 
                      class="form-control" 
                      id="endDate" 
                      v-model="dateRange.end"
                      :min="dateRange.start"
                      required
                    >
                  </div>
                </div>

                <!-- Report Type Specific Filters -->
                <div v-if="selectedType" class="mb-4">
                  <h6 class="mb-3">Filters</h6>
                  
                  <!-- Group By -->
                  <div v-if="hasFilter('group_by')" class="mb-3">
                    <label class="form-label">Group By</label>
                    <select class="form-select" v-model="filters.group_by">
                      <option value="day">Day</option>
                      <option value="week">Week</option>
                      <option value="month">Month</option>
                      <option value="year">Year</option>
                    </select>
                  </div>
                  
                  <!-- Ministry Filter -->
                  <div v-if="hasFilter('ministry_id')" class="mb-3">
                    <label class="form-label">Ministry</label>
                    <select class="form-select" v-model="filters.ministry_id">
                      <option :value="null">All Ministries</option>
                      <option v-for="ministry in ministries" :key="ministry.id" :value="ministry.id">
                        {{ ministry.name }}
                      </option>
                    </select>
                  </div>
                  
                  <!-- Group Filter -->
                  <div v-if="hasFilter('group_id')" class="mb-3">
                    <label class="form-label">Group</label>
                    <select class="form-select" v-model="filters.group_id">
                      <option :value="null">All Groups</option>
                      <option v-for="group in filteredGroups" :key="group.id" :value="group.id">
                        {{ group.name }}
                      </option>
                    </select>
                  </div>
                  
                  <!-- Event Filter -->
                  <div v-if="hasFilter('event_id')" class="mb-3">
                    <label class="form-label">Event</label>
                    <select class="form-select" v-model="filters.event_id" required>
                      <option :value="null" disabled>Select an event</option>
                      <option v-for="event in events" :key="event.id" :value="event.id">
                        {{ event.name }} - {{ formatDate(event.start_time) }}
                      </option>
                    </select>
                  </div>
                </div>

                <!-- Preview Button -->
                <div class="d-flex justify-content-between mt-4">
                  <button 
                    type="button" 
                    class="btn btn-outline-secondary"
                    @click="previewReport"
                    :disabled="loading"
                  >
                    <i class="fas fa-eye me-2"></i>Preview
                  </button>
                  
                  <div>
                    <button 
                      type="button" 
                      class="btn btn-outline-secondary me-2"
                      @click="closeModal"
                      :disabled="loading"
                    >
                      Cancel
                    </button>
                    <button 
                      type="submit" 
                      class="btn btn-primary"
                      :disabled="loading"
                    >
                      <template v-if="loading">
                        <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                        Generating...
                      </template>
                      <template v-else>
                        <i class="fas fa-file-export me-1"></i> Generate Report
                      </template>
                    </button>
                  </div>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Preview Section -->
      <div v-if="showPreview" class="mt-4">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Report Preview</h6>
            <button 
              type="button" 
              class="btn btn-sm btn-outline-secondary"
              @click="exportReport"
              :disabled="exporting"
            >
              <i class="fas fa-download me-1"></i>
              {{ exporting ? 'Exporting...' : 'Export' }}
            </button>
          </div>
          <div class="card-body">
            <div v-if="previewData" class="table-responsive">
              <table class="table table-sm table-hover">
                <thead>
                  <tr>
                    <th v-for="header in previewHeaders" :key="header">
                      {{ formatHeader(header) }}
                    </th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(row, index) in previewData" :key="index">
                    <td v-for="header in previewHeaders" :key="header">
                      {{ formatValue(row[header]) }}
                    </td>
                  </tr>
                </tbody>
              </table>
              
              <div v-if="previewData.length === 0" class="text-center py-4">
                <p class="text-muted mb-0">No data available for the selected filters</p>
              </div>
            </div>
            
            <div v-else class="text-center py-5">
              <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading preview...</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <template #footer>
      <div class="d-flex justify-content-between w-100">
        <div>
          <button 
            v-if="showPreview" 
            type="button" 
            class="btn btn-outline-secondary"
            @click="showPreview = false"
          >
            <i class="fas fa-arrow-left me-1"></i> Back to Editor
          </button>
        </div>
        
        <div>
          <button 
            type="button" 
            class="btn btn-outline-secondary me-2"
            @click="closeModal"
          >
            Cancel
          </button>
          <button 
            v-if="showPreview"
            type="button" 
            class="btn btn-primary"
            @click="saveReport"
            :disabled="saving"
          >
            <i class="fas fa-save me-1"></i> {{ saving ? 'Saving...' : 'Save Report' }}
          </button>
        </div>
      </div>
    </template>
  </modal>
</template>

<script>
import { ref, computed, onMounted } from 'vue';
import { format } from 'date-fns';
import axios from 'axios';

export default {
  name: 'ReportBuilderModal',
  
  props: {
    modelValue: {
      type: Boolean,
      required: true
    }
  },
  
  emits: ['update:modelValue', 'report-generated'],
  
  setup(props, { emit }) {
    const reportTypes = ref([]);
    const selectedType = ref(null);
    const reportName = ref('');
    const loading = ref(false);
    const saving = ref(false);
    const exporting = ref(false);
    const showPreview = ref(false);
    const previewData = ref(null);
    const previewHeaders = ref([]);
    const ministries = ref([]);
    const groups = ref([]);
    const events = ref([]);
    
    const dateRange = ref({
      start: format(new Date(), 'yyyy-MM-01'), // First day of current month
      end: format(new Date(), 'yyyy-MM-dd')   // Today
    });
    
    const filters = ref({
      group_by: 'week',
      ministry_id: null,
      group_id: null,
      event_id: null
    });
    
    const filteredGroups = computed(() => {
      if (!filters.value.ministry_id) return groups.value;
      return groups.value.filter(group => group.ministry_id == filters.value.ministry_id);
    });
    
    const loadReportTypes = async () => {
      try {
        const response = await axios.get('/api/reports/types');
        reportTypes.value = response.data.types || [];
      } catch (error) {
        console.error('Error loading report types:', error);
      }
    };
    
    const loadReferenceData = async () => {
      try {
        const [ministriesRes, groupsRes, eventsRes] = await Promise.all([
          axios.get('/api/ministries'),
          axios.get('/api/groups'),
          axios.get('/api/events', {
            params: {
              start_date: dateRange.value.start,
              end_date: dateRange.value.end,
              per_page: 100
            }
          })
        ]);
        
        ministries.value = ministriesRes.data.data || [];
        groups.value = groupsRes.data.data || [];
        events.value = eventsRes.data.data || [];
      } catch (error) {
        console.error('Error loading reference data:', error);
      }
    };
    
    const selectReportType = (type) => {
      selectedType.value = type;
      reportName.value = type.name;
      resetFilters();
      showPreview.value = false;
      previewData.value = null;
    };
    
    const resetFilters = () => {
      filters.value = {
        group_by: 'week',
        ministry_id: null,
        group_id: null,
        event_id: null
      };
    };
    
    const hasFilter = (filterName) => {
      if (!selectedType.value) return false;
      const type = reportTypes.value.find(t => t.id === selectedType.value.id);
      return type?.filters?.[filterName] !== undefined;
    };
    
    const formatDate = (dateString) => {
      if (!dateString) return '';
      return format(new Date(dateString), 'MMM d, yyyy h:mm a');
    };
    
    const formatValue = (value) => {
      if (value === null || value === undefined) return '-';
      if (typeof value === 'boolean') return value ? 'Yes' : 'No';
      if (value instanceof Date) return formatDate(value);
      return value;
    };
    
    const formatHeader = (header) => {
      return header
        .split('_')
        .map(word => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
    };
    
    const previewReport = async () => {
      if (!selectedType.value) return;
      
      loading.value = true;
      showPreview.value = true;
      previewData.value = null;
      
      try {
        const params = {
          report_type: selectedType.value.id,
          start_date: dateRange.value.start,
          end_date: dateRange.value.end,
          ...filters.value
        };
        
        const response = await axios.get('/api/reports/generate', { params });
        
        // Extract headers from the first data item if available
        if (response.data.length > 0) {
          previewHeaders.value = Object.keys(response.data[0]);
        } else {
          previewHeaders.value = [];
        }
        
        previewData.value = response.data;
      } catch (error) {
        console.error('Error generating preview:', error);
        // Show error to user
      } finally {
        loading.value = false;
      }
    };
    
    const generateReport = async () => {
      if (!selectedType.value) return;
      
      loading.value = true;
      
      try {
        const params = {
          name: reportName.value,
          report_type: selectedType.value.id,
          start_date: dateRange.value.start,
          end_date: dateRange.value.end,
          filters: filters.value
        };
        
        const response = await axios.post('/api/reports', params);
        
        // Emit event with the generated report
        emit('report-generated', response.data);
        closeModal();
      } catch (error) {
        console.error('Error generating report:', error);
        // Show error to user
      } finally {
        loading.value = false;
      }
    };
    
    const saveReport = async () => {
      saving.value = true;
      
      try {
        const params = {
          name: reportName.value,
          report_type: selectedType.value.id,
          start_date: dateRange.value.start,
          end_date: dateRange.value.end,
          filters: filters.value,
          data: previewData.value
        };
        
        const response = await axios.post('/api/reports/save', params);
        
        // Emit event with the saved report
        emit('report-generated', response.data);
        closeModal();
      } catch (error) {
        console.error('Error saving report:', error);
        // Show error to user
      } finally {
        saving.value = false;
      }
    };
    
    const exportReport = async () => {
      if (!previewData.value) return;
      
      exporting.value = true;
      
      try {
        const params = {
          name: reportName.value || 'report',
          data: previewData.value,
          format: 'csv' // Could be parameterized
        };
        
        const response = await axios.post('/api/reports/export', params, {
          responseType: 'blob'
        });
        
        // Create download link
        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', `${params.name}.${params.format}`);
        document.body.appendChild(link);
        link.click();
        link.remove();
      } catch (error) {
        console.error('Error exporting report:', error);
        // Show error to user
      } finally {
        exporting.value = false;
      }
    };
    
    const closeModal = () => {
      emit('update:modelValue', false);
      // Reset form after animation
      setTimeout(() => {
        selectedType.value = null;
        reportName.value = '';
        showPreview.value = false;
        previewData.value = null;
        loading.value = false;
      }, 300);
    };
    
    onMounted(() => {
      loadReportTypes();
      loadReferenceData();
    });
    
    return {
      reportTypes,
      selectedType,
      reportName,
      dateRange,
      filters,
      loading,
      saving,
      exporting,
      showPreview,
      previewData,
      previewHeaders,
      ministries,
      groups,
      events,
      filteredGroups,
      selectReportType,
      hasFilter,
      formatDate,
      formatValue,
      formatHeader,
      previewReport,
      generateReport,
      saveReport,
      exportReport,
      closeModal
    };
  }
};
</script>

<style scoped>
.report-builder {
  min-height: 600px;
}

.list-group-item {
  border-left: none;
  border-right: none;
  border-radius: 0 !important;
  cursor: pointer;
  transition: all 0.2s;
}

.list-group-item:first-child {
  border-top: none;
}

.list-group-item:hover {
  background-color: #f8f9fa;
}

.list-group-item.active {
  background-color: #4e73df;
  border-color: #4e73df;
}

.card {
  border: 1px solid #e3e6f0;
  box-shadow: none;
  margin-bottom: 0;
}

.card-header {
  background-color: #f8f9fc;
  border-bottom: 1px solid #e3e6f0;
  padding: 0.75rem 1.25rem;
}

.table th {
  font-weight: 600;
  text-transform: uppercase;
  font-size: 0.7rem;
  letter-spacing: 0.05em;
  color: #5a5c69;
  border-top: none;
}

.table td {
  vertical-align: middle;
}

.btn-close {
  font-size: 0.8rem;
  padding: 0.5rem 0.5rem;
  margin: -0.5rem -0.5rem -0.5rem auto;
}
</style>
