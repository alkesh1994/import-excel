<template>
  <a-modal
    title="Upload excel"
    v-model="visible"
    cancel-text="Close"
    ok-text="Confirm"
    :closable="false"
    :maskClosable="false"
    destroyOnClose
  >
    <a-upload-dragger
      name="file"
      :multiple="false"
      :showUploadList="false"
      :action="`/import`"
      @change="handleChange"
    >
      <p class="ant-upload-drag-icon">
        <a-icon type="inbox" />
      </p>
      <p class="ant-upload-text">Click to upload</p>
    </a-upload-dragger>
    <a-progress class="mt-5" :percent="progress" :show-info="false" />
    <div class="text-right mt-1">{{ this.current_row }} / {{ this.total_rows }}</div>
    <template slot="footer">
      <a-button @click="close">Close</a-button>
    </template>
  </a-modal>
</template>

<script>
export default {

  // components: {
  //   "a-modal": Modal,
  //   "a-upload-dragger": UploadDragger,
  //   "a-progress":Progress,
  //   "button":Button,
  // },
  data() {
    this.trackProgress = _.debounce(this.trackProgress, 1000);

    return {
      visible: true,
      current_row: 0,
      total_rows: 0,
      progress: 0,
    };
  },
  methods: {
    handleChange(info) {
      const status = info.file.status;

      if (status === "done") {
        this.trackProgress();
      } else if (status === "error") {
        this.$message.error(_.get(info, 'file.response.errors.file.0', `${info.file.name} file upload failed.`));
      }
    },

    async trackProgress() {
      const { data } = await axios.get('/import-status');

      if (data.finished) {
        this.current_row = this.total_rows
        this.progress = 100
        return;
      };

      this.total_rows = data.total_rows;
      this.current_row = data.current_row;
      this.progress = Math.ceil(data.current_row / data.total_rows * 100);
      this.trackProgress();
    },

    close() {
      if (this.progress > 0 && this.progress < 100) {
        if (confirm('Do you want to close')) {
          this.$emit('close')
          window.location.reload()
        }
      } else {
        this.$emit('close')
        window.location.reload()
      }
    }
  },
};
</script>
