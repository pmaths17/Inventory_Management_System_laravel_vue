<template>
  <div class="card stats-card border-0 shadow-sm h-100">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <p class="text-muted mb-1 text-uppercase small">{{ title }}</p>
          <h3 class="mb-0 font-weight-bold">{{ value }}</h3>
          <!-- <small :class="trendClass">
            <i :class="trendIcon"></i> {{ trend }}
          </small> -->
        </div>
        <div :class="iconClass">
          <i :class="icon + ' fa-2x'"></i>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'StatsCard',
  props: {
    title: {
      type: String,
      required: true
    },
    value: {
      type: [String, Number],
      required: true
    },
    icon: {
      type: String,
      required: true
    },
    color: {
      type: String,
      default: 'primary'
    },
    // trend: {
    //   type: String,
    //   default: ''
    // },
    // trendUp: {
    //   type: Boolean,
    //   default: true
    // }
  },
  computed: {
    iconClass() {
      return `stats-icon bg-${this.color}`;
    }
    // trendClass() {
    //   return this.trendUp ? 'text-success' : 'text-danger';
    // },
    // trendIcon() {
    //   return this.trendUp ? 'fas fa-arrow-up' : 'fas fa-arrow-down';
    // }
  }
};
</script>

<style scoped>
.stats-card {
  border-radius: 12px;
  transition: transform 0.2s;
}

.stats-card:hover {
  transform: translateY(-5px);
}

.stats-icon {
  width: 60px;
  height: 60px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  opacity: 0.9;
}
</style>