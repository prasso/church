<template>
  <div class="reports-dashboard">
    <div class="container-fluid">
      <!-- Header -->
      <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Reports & Analytics</h1>
        <button class="btn btn-primary" @click="showReportBuilder = true">
          <i class="fas fa-plus me-2"></i>New Report
        </button>
      </div>

      <!-- Quick Stats -->
      <div class="row mb-4">
        <div class="col-md-3 mb-3" v-for="stat in stats" :key="stat.title">
          <div class="card h-100">
            <div class="card-body">
              <h6 class="text-muted mb-2">{{ stat.title }}</h6>
              <h3 class="mb-0">{{ stat.value }}</h3>
              <p class="mb-0 text-muted" v-if="stat.change !== null">
                <span :class="stat.change >= 0 ? 'text-success' : 'text-danger'">
                  <i :class="stat.change >= 0 ? 'fas fa-arrow-up' : 'fas fa-arrow-down'"></i>
                  {{ Math.abs(stat.change) }}%
                </span>
                vs last period
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Main Charts -->
      <div class="row">
        <!-- Attendance Trends -->
        <div class="col-lg-8 mb-4">
          <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
              <h5 class="mb-0">Attendance Trends</h5>
              <div class="btn-group">
                <button 
                  v-for="period in timePeriods" 
                  :key="period.value"
                  @click="loadAttendanceTrends(period.value)"
                  :class="['btn btn-sm', activePeriod === period.value ? 'btn-primary' : 'btn-outline-secondary']"
                >
                  {{ period.label }}
                </button>
              </div>
            </div>
            <div class="card-body">
              <line-chart 
                v-if="attendanceTrends.length > 0" 
                :data="formatChartData(attendanceTrends, 'period', 'total_attendees')"
                :options="chartOptions"
              />
              <div v-else class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                  <span class="visually-hidden">Loading...</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Member Engagement -->
        <div class="col-lg-4 mb-4">
          <div class="card h-100">
            <div class="card-header">
              <h5 class="mb-0">Member Engagement</h5>
            </div>
            <div class="card-body">
              <div class="text-center mb-4">
                <div class="position-relative d-inline-block">
                  <vue-ellipse-progress
                    :progress="engagementScore"
                    :size="200"
                    :thickness="15"
                    :empty-thickness="10"
                    :empty-color="'#e9ecef'"
                    :color="getEngagementColor(engagementScore)"
                    :line="'butt'"
                    :font-size="'1.5rem'"
                    :loading="false"
                    :no-data="false"
                    :legend="false"
                  >
                    <template #default>
                      <div class="text-center">
                        <div class="h2 mb-0">{{ engagementScore }}%</div>
                        <div class="text-muted small">Engagement</div>
                      </div>
                    </template>
                  </vue-ellipse-progress>
                </div>
              </div>

              <div class="list-group list-group-flush">
                <div class="list-group-item d-flex justify-content-between align-items-center">
                  <div>
                    <h6 class="mb-0">Active Members</h6>
                    <small class="text-muted">Last 30 days</small>
                  </div>
                  <span class="badge bg-primary rounded-pill">{{ engagement.active_members || 0 }}</span>
                </div>
                <div class="list-group-item d-flex justify-content-between align-items-center">
                  <div>
                    <h6 class="mb-0">New Members</h6>
                    <small class="text-muted">This month</small>
                  </div>
                  <span class="badge bg-success rounded-pill">{{ engagement.new_members || 0 }}</span>
                </div>
                <div class="list-group-item d-flex justify-content-between align-items-center">
                  <div>
                    <h6 class="mb-0">Avg. Attendance</h6>
                    <small class="text-muted">Per event</small>
                  </div>
                  <span class="badge bg-info rounded-pill">{{ engagement.attendance_rate || 0 }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Events -->
      <div class="row">
        <div class="col-12">
          <div class="card">
            <div class="card-header">
              <h5 class="mb-0">Recent Events</h5>
            </div>
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table table-hover mb-0">
                  <thead>
                    <tr>
                      <th>Event</th>
                      <th>Date</th>
                      <th>Type</th>
                      <th>Attendees</th>
                      <th>Guests</th>
                      <th>Engagement</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="event in recentEvents" :key="event.id">
                      <td>{{ event.name }}</td>
                      <td>{{ formatDate(event.start_time) }}</td>
                      <td>{{ event.type }}</td>
                      <td>{{ event.total_attendees || 0 }}</td>
                      <td>{{ event.guest_count || 0 }}</td>
                      <td>
                        <div class="progress" style="height: 20px;">
                          <div 
                            class="progress-bar bg-success" 
                            role="progressbar" 
                            :style="{ width: calculateEngagement(event) + '%' }"
                            :aria-valuenow="calculateEngagement(event)" 
                            aria-valuemin="0" 
                            aria-valuemax="100"
                          >{{ calculateEngagement(event) }}%</div>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Report Builder Modal -->
    <report-builder-modal 
      v-model="showReportBuilder" 
      @report-generated="handleReportGenerated"
    />
  </div>
</template>

<script>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { format } from 'date-fns';
import axios from 'axios';
import LineChart from '@/Components/Charts/LineChart.vue';
import ReportBuilderModal from './ReportBuilderModal.vue';
import { VueEllipseProgress } from 'vue-ellipse-progress';

export default {
  components: {
    LineChart,
    ReportBuilderModal,
    VueEllipseProgress
  },
  
  setup() {
    const router = useRouter();
    const stats = ref([
      { title: 'Total Members', value: '0', change: null },
      { title: 'Active Members', value: '0', change: 12 },
      { title: 'Avg. Attendance', value: '0', change: 5 },
      { title: 'Events This Month', value: '0', change: -2 }
    ]);
    
    const attendanceTrends = ref([]);
    const engagement = ref({});
    const engagementScore = ref(0);
    const recentEvents = ref([]);
    const activePeriod = ref('month');
    const showReportBuilder = ref(false);
    
    const timePeriods = [
      { label: 'Week', value: 'week' },
      { label: 'Month', value: 'month' },
      { label: 'Quarter', value: 'quarter' },
      { label: 'Year', value: 'year' }
    ];
    
    const chartOptions = {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: false
        },
        tooltip: {
          mode: 'index',
          intersect: false
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            precision: 0
          }
        }
      }
    };
    
    const loadDashboardData = async () => {
      try {
        const [dashboardRes, trendsRes] = await Promise.all([
          axios.get('/api/reports/dashboard'),
          axios.get(`/api/reports/attendance-trends?period=${activePeriod.value}`)
        ]);
        
        const data = dashboardRes.data;
        
        // Update stats
        stats.value = [
          { title: 'Total Members', value: data.member_count || 0, change: null },
          { title: 'Active Members', value: data.engagement?.active_members || 0, change: 12 },
          { title: 'Avg. Attendance', value: data.engagement?.avg_attendance || 0, change: 5 },
          { title: 'Events This Month', value: data.recent_events?.length || 0, change: -2 }
        ];
        
        // Update engagement
        engagement.value = data.engagement || {};
        engagementScore.value = data.engagement?.engagement_score || 0;
        
        // Update recent events
        recentEvents.value = data.recent_events || [];
        
        // Update attendance trends
        attendanceTrends.value = trendsRes.data || [];
        
      } catch (error) {
        console.error('Error loading dashboard data:', error);
      }
    };
    
    const loadAttendanceTrends = async (period) => {
      try {
        activePeriod.value = period;
        const response = await axios.get(`/api/reports/attendance-trends?period=${period}`);
        attendanceTrends.value = response.data || [];
      } catch (error) {
        console.error('Error loading attendance trends:', error);
      }
    };
    
    const formatChartData = (data, labelKey, valueKey) => {
      return {
        labels: data.map(item => item[labelKey]),
        datasets: [
          {
            label: 'Attendees',
            data: data.map(item => item[valueKey]),
            borderColor: '#4e73df',
            backgroundColor: 'rgba(78, 115, 223, 0.1)',
            fill: true,
            tension: 0.4
          }
        ]
      };
    };
    
    const formatDate = (dateString) => {
      return dateString ? format(new Date(dateString), 'MMM d, yyyy h:mm a') : '';
    };
    
    const calculateEngagement = (event) => {
      if (!event || !event.total_attendees || !event.expected_attendees) return 0;
      return Math.min(100, Math.round((event.total_attendees / event.expected_attendees) * 100));
    };
    
    const getEngagementColor = (score) => {
      if (score >= 70) return '#1cc88a';
      if (score >= 40) return '#f6c23e';
      return '#e74a3b';
    };
    
    const handleReportGenerated = (report) => {
      showReportBuilder.value = false;
      // Navigate to the report view or show a success message
      router.push(`/reports/${report.id}`);
    };
    
    onMounted(() => {
      loadDashboardData();
    });
    
    return {
      stats,
      attendanceTrends,
      engagement,
      engagementScore,
      recentEvents,
      activePeriod,
      timePeriods,
      showReportBuilder,
      chartOptions,
      loadAttendanceTrends,
      formatChartData,
      formatDate,
      calculateEngagement,
      getEngagementColor,
      handleReportGenerated
    };
  }
};
</script>

<style scoped>
.reports-dashboard {
  padding: 1.5rem 0;
}

.card {
  border: none;
  box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
  margin-bottom: 1.5rem;
}

.card-header {
  background-color: #f8f9fc;
  border-bottom: 1px solid #e3e6f0;
  padding: 1rem 1.25rem;
}

.progress {
  height: 0.5rem;
  border-radius: 0.35rem;
}

.table th {
  border-top: none;
  font-weight: 600;
  text-transform: uppercase;
  font-size: 0.7rem;
  letter-spacing: 0.05em;
  color: #5a5c69;
  padding: 1rem;
}

.table td {
  vertical-align: middle;
  padding: 1rem;
}
</style>
