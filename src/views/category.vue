<template>
  <div class="container mt-4">
    <h2 class="mb-4">จัดการหมวดหมู่สินค้า</h2>

    <div v-if="error" class="alert alert-danger" role="alert">{{ error }}</div>
    <div v-if="notice" class="alert alert-success" role="status">{{ notice }}</div>

    <form class="card card-body mb-4" @submit.prevent="saveCategory">
      <label for="category-name" class="form-label fw-bold">
        {{ editingId ? "แก้ไขชื่อหมวดหมู่" : "เพิ่มหมวดหมู่ใหม่" }}
      </label>
      <div class="d-flex flex-column flex-sm-row gap-2">
        <input
          id="category-name"
          v-model.trim="categoryName"
          class="form-control"
          type="text"
          maxlength="100"
          placeholder="ชื่อหมวดหมู่"
          required
        />
        <div class="d-flex gap-2">
          <button class="btn btn-primary text-nowrap" type="submit" :disabled="saving">
            {{ saving ? "กำลังบันทึก..." : editingId ? "บันทึกการแก้ไข" : "เพิ่มหมวดหมู่" }}
          </button>
          <button
            v-if="editingId"
            class="btn btn-outline-secondary"
            type="button"
            :disabled="saving"
            @click="resetForm"
          >
            ยกเลิก
          </button>
        </div>
      </div>
    </form>

    <div v-if="loading" class="text-center py-4">กำลังโหลดหมวดหมู่...</div>
    <div v-else-if="categories.length === 0" class="alert alert-info">
      ยังไม่มีหมวดหมู่สินค้า
    </div>
    <div v-else class="table-responsive">
      <table class="table table-bordered table-striped align-middle">
        <thead class="table-primary">
          <tr>
            <th class="text-center">ID</th>
            <th>ชื่อหมวดหมู่</th>
            <th class="text-center">จำนวนสินค้า</th>
            <th class="text-center">จัดการ</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="category in categories" :key="category.category_id">
            <td class="text-center">{{ category.category_id }}</td>
            <td>{{ category.category_name }}</td>
            <td class="text-center">{{ category.product_count }}</td>
            <td class="text-center text-nowrap">
              <button
                class="btn btn-warning btn-sm me-2"
                type="button"
                :disabled="saving"
                @click="editCategory(category)"
              >
                แก้ไข
              </button>
              <button
                class="btn btn-danger btn-sm"
                type="button"
                :disabled="saving"
                @click="deleteCategory(category)"
              >
                ลบ
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script>
import { onMounted, ref } from "vue";
import { apiUrl } from "../api";

export default {
  name: "Category",
  setup() {
    const categories = ref([]);
    const categoryName = ref("");
    const editingId = ref(null);
    const loading = ref(true);
    const saving = ref(false);
    const error = ref("");
    const notice = ref("");

    const fetchCategories = async () => {
      loading.value = true;
      error.value = "";
      try {
        const response = await fetch(apiUrl("api_category.php"));
        const result = await response.json();
        if (!response.ok || !result.success) {
          throw new Error(result.message || "ไม่สามารถโหลดหมวดหมู่ได้");
        }
        categories.value = result.data;
      } catch (err) {
        error.value = err.message;
      } finally {
        loading.value = false;
      }
    };

    const resetForm = () => {
      categoryName.value = "";
      editingId.value = null;
    };

    const editCategory = (category) => {
      notice.value = "";
      error.value = "";
      editingId.value = category.category_id;
      categoryName.value = category.category_name;
    };

    const saveCategory = async () => {
      saving.value = true;
      error.value = "";
      notice.value = "";
      try {
        const formData = new FormData();
        formData.append("action", editingId.value ? "update" : "add");
        formData.append("category_name", categoryName.value);
        if (editingId.value) formData.append("category_id", editingId.value);

        const response = await fetch(apiUrl("api_category.php"), {
          method: "POST",
          body: formData,
        });
        const result = await response.json();
        if (!response.ok || !result.success) {
          throw new Error(result.message || "ไม่สามารถบันทึกหมวดหมู่ได้");
        }

        notice.value = result.message;
        resetForm();
        await fetchCategories();
      } catch (err) {
        error.value = err.message;
      } finally {
        saving.value = false;
      }
    };

    const deleteCategory = async (category) => {
      if (!confirm(`ยืนยันการลบหมวดหมู่ "${category.category_name}" หรือไม่?`)) return;

      saving.value = true;
      error.value = "";
      notice.value = "";
      try {
        const formData = new FormData();
        formData.append("action", "delete");
        formData.append("category_id", category.category_id);

        const response = await fetch(apiUrl("api_category.php"), {
          method: "POST",
          body: formData,
        });
        const result = await response.json();
        if (!response.ok || !result.success) {
          throw new Error(result.message || "ไม่สามารถลบหมวดหมู่ได้");
        }

        notice.value = result.message;
        if (editingId.value === category.category_id) resetForm();
        await fetchCategories();
      } catch (err) {
        error.value = err.message;
      } finally {
        saving.value = false;
      }
    };

    onMounted(fetchCategories);

    return {
      categories,
      categoryName,
      editingId,
      loading,
      saving,
      error,
      notice,
      editCategory,
      resetForm,
      saveCategory,
      deleteCategory,
    };
  },
};
</script>
