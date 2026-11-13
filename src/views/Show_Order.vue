<template>
  <div class="container my-5">
    <h2 class="text-center mb-4">📋 รายการคำสั่งซื้อทั้งหมด</h2>

    <!-- 🔍 ตัวกรองค้นหาและสถานะ -->
    <div class="mb-3 d-flex justify-content-between align-items-center">
      <div class="d-flex">
        <select v-model="searchBy" class="form-select me-2 w-auto">
          <option value="table_no">ค้นหาตามโต๊ะ</option>
          <option value="order_id">ค้นหาตามรหัสคำสั่งซื้อ</option>
        </select>
        <input
          type="text"
          class="form-control me-2"
          :placeholder="searchBy === 'table_no' ? '🔍 กรอกหมายเลขโต๊ะ...' : '🔍 กรอกรหัสคำสั่งซื้อ...'"
          v-model="searchKeyword"
        />
        <select v-model="statusFilter" class="form-select w-auto">
          <option value="">ทุกสถานะ</option>
          <option value="รอดำเนินการ">รอดำเนินการ</option>
          <option value="ยกเลิก">ยกเลิก</option>
          <option value="เสร็จแล้ว">เสร็จแล้ว</option>
        </select>
      </div>

      <div>
        <label class="me-2">แสดงต่อหน้า:</label>
        <select v-model.number="rowsPerPage" class="form-select d-inline-block w-auto">
          <option :value="5">5</option>
          <option :value="10">10</option>
          <option :value="20">20</option>
          <option :value="50">50</option>
        </select>
      </div>
    </div>

    <!-- ➕ ปุ่มลบรายการยกเลิกทั้งหมด -->
    <div class="mb-3 text-end">
      <button class="btn btn-danger" @click="deleteAllCancelledOrders">
        ลบรายการยกเลิกทั้งหมด
      </button>
    </div>

    <!-- ⏳ กำลังโหลด -->
    <div v-if="loading" class="text-center">⏳ กำลังโหลดข้อมูล...</div>
    <div v-if="error" class="text-danger text-center">{{ error }}</div>

    <!-- 📋 ตารางแสดงข้อมูล -->
    <table v-if="paginatedOrders.length > 0" class="table table-bordered table-striped mt-3">
      <thead class="table-primary text-center">
        <tr>
          <th>รหัสคำสั่งซื้อ</th>
          <th>โต๊ะ</th>
          <th>รหัสสินค้า</th>
          <th>สินค้า</th>
          <th>จำนวน</th>
          <th>ราคา/หน่วย</th>
          <th>ราคารวม</th>
          <th>วันที่สั่ง</th>
          <th>สถานะ</th>
          <th>จัดการ</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(order, index) in paginatedOrders" :key="index">
          <td>{{ order.order_id }}</td>
          <td>{{ order.table_no }}</td>
          <td>{{ order.product_id }}</td>
          <td>{{ order.product_name }}</td>
          <td>{{ order.quantity }}</td>
          <td>{{ order.price.toFixed(2) }}</td>
          <td>{{ order.subtotal.toFixed(2) }}</td>
          <td>{{ order.order_date }}</td>
          <td>
            <select v-model="order.status" @change="updateStatus(order)">
              <option value="รอดำเนินการ">รอดำเนินการ</option>
              <option value="ยกเลิก">ยกเลิก</option>
              <option value="เสร็จแล้ว">เสร็จแล้ว</option>
            </select>
          </td>
          <td class="text-center">
            <button
              v-if="order.status === 'ยกเลิก'"
              class="btn btn-danger btn-sm"
              @click="deleteOrder(order)"
            >
              ลบ
            </button>
          </td>
        </tr>
      </tbody>
    </table>

    <div v-else-if="!loading" class="text-center text-muted">
      ❗ ยังไม่มีข้อมูลคำสั่งซื้อ
    </div>

    <!-- 📄 ปุ่มเปลี่ยนหน้า -->
    <div v-if="totalPages > 1" class="d-flex justify-content-center align-items-center mt-4">
      <button class="btn btn-secondary me-2" :disabled="currentPage === 1" @click="currentPage--">
        ⬅ ก่อนหน้า
      </button>
      <span>หน้า {{ currentPage }} จาก {{ totalPages }}</span>
      <button class="btn btn-secondary ms-2" :disabled="currentPage === totalPages" @click="currentPage++">
        ถัดไป ➡
      </button>
    </div>
  </div>
</template>

<script>
import { ref, onMounted, computed, watch } from "vue";
import { apiUrl } from "../api";

export default {
  name: "OrderList",
  setup() {
    const orders = ref([]);
    const loading = ref(true);
    const error = ref(null);
    const searchKeyword = ref("");
    const searchBy = ref("table_no");
    const statusFilter = ref("");
    const currentPage = ref(1);
    const rowsPerPage = ref(10);

    const fetchOrders = async () => {
      loading.value = true;
      error.value = null;
      try {
        const res = await fetch(apiUrl("show_orders.php"));
        const data = await res.json();
        if (data.success && Array.isArray(data.data)) {
          orders.value = data.data.map((o) => ({
            order_id: o.order_id,
            table_no: o.table_no,
            product_id: o.product_id,
            product_name: o.product_name,
            quantity: Number(o.quantity),
            price: Number(o.price),
            subtotal: Number(o.subtotal || o.price * o.quantity),
            order_date: o.order_date,
            status: o.status || "รอดำเนินการ",
          }));
        } else {
          error.value = data.message || "ไม่สามารถโหลดข้อมูลได้";
        }
      } catch (err) {
        error.value = "เกิดข้อผิดพลาด: " + err.message;
      } finally {
        loading.value = false;
      }
    };

    onMounted(fetchOrders);

    const filteredOrders = computed(() => {
      let result = orders.value;
      if (statusFilter.value) {
        result = result.filter(order => order.status === statusFilter.value);
      }
      if (searchKeyword.value) {
        const keyword = searchKeyword.value.toString().toLowerCase();
        result = result.filter(order => {
          if (searchBy.value === "table_no") return order.table_no.toString().toLowerCase().includes(keyword);
          if (searchBy.value === "order_id") return order.order_id.toString().toLowerCase().includes(keyword);
          return true;
        });
      }
      return result;
    });

    const totalPages = computed(() => Math.ceil(filteredOrders.value.length / rowsPerPage.value));
    const paginatedOrders = computed(() => {
      const start = (currentPage.value - 1) * rowsPerPage.value;
      return filteredOrders.value.slice(start, start + rowsPerPage.value);
    });

    watch([rowsPerPage, statusFilter, searchKeyword], () => { currentPage.value = 1; });

    const updateStatus = async (order) => {
      try {
        const res = await fetch(apiUrl("update_order_status.php"), {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ order_id: order.order_id, product_id: order.product_id, status: order.status }),
        });
        const data = await res.json();
        if (!data.success) alert("อัปเดตสถานะไม่สำเร็จ: " + (data.message || "เกิดข้อผิดพลาด"));
        await fetchOrders();
      } catch (err) { alert("เกิดข้อผิดพลาด: " + err.message); }
    };

    const deleteOrder = async (order) => {
      if (!confirm(`คุณต้องการลบคำสั่งซื้อ ${order.order_id} หรือไม่?`)) return;
      try {
        const res = await fetch(apiUrl("delete_order.php"), {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ order_id: order.order_id, product_id: order.product_id }),
        });
        const data = await res.json();
        if (data.success) {
          alert("ลบคำสั่งซื้อเรียบร้อยแล้ว");
          await fetchOrders();
        } else {
          alert("ลบไม่สำเร็จ: " + (data.message || "เกิดข้อผิดพลาด"));
        }
      } catch (err) {
        alert("เกิดข้อผิดพลาด: " + err.message);
      }
    };

    // ✅ ฟังก์ชันลบรายการยกเลิกทั้งหมด
    const deleteAllCancelledOrders = async () => {
      if (!confirm("คุณต้องการลบคำสั่งซื้อที่ยกเลิกทั้งหมดหรือไม่?")) return;
      try {
        const res = await fetch(apiUrl("delete_cancelled_orders.php"), {
          method: "POST",
          headers: { "Content-Type": "application/json" },
        });
        const data = await res.json();
        if (data.success) {
          alert("ลบคำสั่งซื้อที่ยกเลิกทั้งหมดเรียบร้อยแล้ว");
          await fetchOrders();
        } else {
          alert("ลบไม่สำเร็จ: " + (data.message || "เกิดข้อผิดพลาด"));
        }
      } catch (err) {
        alert("เกิดข้อผิดพลาด: " + err.message);
      }
    };

    return {
      orders, loading, error, searchKeyword, searchBy, statusFilter,
      currentPage, rowsPerPage, totalPages, paginatedOrders,
      updateStatus, deleteOrder, deleteAllCancelledOrders
    };
  },
};
</script>

<style scoped>
.table { font-size: 0.95rem; }
select { border-radius: 8px; padding: 4px 8px; }
</style>
