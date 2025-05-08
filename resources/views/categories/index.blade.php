@extends('layouts.app')

@section('title', 'Category Management')

@section('content')
<div class="container-fluid">
    <!-- Sidebar for Categories Tree -->
    <div class="sidebar">
        <h5>Categories Tree</h5>
        <div class="tree" id="category-tree">
            <!-- Tree will be populated dynamically -->
        </div>
    </div>

    <!-- Main Content -->
    <div class="content">
        <!-- Filter Section -->
        <div class="row mb-3">
            <div class="col-md-3">
                <input type="text" id="search-input" class="form-control" placeholder="Search for a category...">
            </div>
            <div class="col-md-3">
                <select id="parent-filter" class="form-control">
                    <option value="">All Categories</option>
                    <!-- Options will be populated dynamically -->
                </select>
            </div>
            <div class="col-md-3">
                <button class="btn btn-danger" id="delete-selected">Delete the selected</button>
            </div>
            <div class="col-md-3">
                <button class="btn btn-success" id="addCategoryBtn" style="float: right;">Add new category</button>
            </div>
        </div>

        <!-- Categories Table -->
        <table class="table table-bordered" id="categories-table">
            <thead>
                <tr>
                    <th><input type="checkbox" id="select-all"></th>
                    <th>Index</th>
                    <th>Category Name</th>
                    <th>Parent</th>
                    <th>Edit</th>
                    <th>Delete</th>
                </tr>
            </thead>
            <tbody>
                <!-- Table rows will be populated dynamically -->
            </tbody>
        </table>

        <!-- Pagination -->
        <div id="pagination" class="d-flex justify-content-between"></div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="categoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="categoryModalLabel">Edit Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="categoryForm">
                    <input type="hidden" id="categoryId">
                    <div class="mb-3">
                        <label class="form-label">Category Name</label>
                        <input type="text" id="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Parent</label>
                        <select id="parent_id" class="form-control">
                            <option value="">No Parent</option>
                            <!-- Options will be populated dynamically -->
                        </select>
                    </div>
                    <button type="button" class="btn btn-primary" id="saveCategoryBtn">Save</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm deletion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this category?</p>
                <input type="hidden" id="delete-id">
                <button class="btn btn-danger" id="confirm-delete">Delete</button>
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .sidebar {
        width: 20%;
        float: left;
        padding: 10px;
        border-right: 1px solid #ccc;
        overflow-x: scroll;
    }
    .content {
        width: 80%;
        padding: 20px;
        position: fixed;
        right: 0;
    }
    .tree {
        list-style-type: none;
    }
    .tree li {
        margin-left: 20px;
    }
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function () {
    const searchInput = $('#search-input');
    const parentFilter = $('#parent-filter');
    const selectAllCheckbox = $('#select-all');
    const deleteSelectedBtn = $('#delete-selected');
    const categoriesTableBody = $('#categories-table tbody');
    const paginationContainer = $('#pagination');
    const categoryForm = $('#categoryForm');
    const saveCategoryBtn = $('#saveCategoryBtn');
    const addCategoryBtn = $('#addCategoryBtn');
    const categoryModal = $('#categoryModal');
    const categoryModalLabel = $('#categoryModalLabel');
    const deleteModal = $('#deleteModal');
    const confirmDeleteBtn = $('#confirm-delete');
    const categoryTree = $('#category-tree');
    
    let currentPage = 1;
    let perPage = 10;
    let keyword = '';
    let parentId = '';
    let selectedCategories = [];
    let allCategoriesSelected = false;
    let totalCategories = 0;

    // Load categories with search, filter, and pagination
    function loadCategories() {
        const url = `/api/categories?page=${currentPage}&per_page=${perPage}&keyword=${encodeURIComponent(keyword)}&with=parent${parentId ? '&parent_id=' + parentId : ''}`;
        $.ajax({
            url: url,
            method: 'GET',
            success: function (response) {
                if (response.status) {
                    totalCategories = response.data.total;
                    renderCategories(response.data.categories, response.data.page);
                    renderPagination(response.data.total, response.data.per_page);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to fetch categories: ' + (response.error || 'Unknown error'),
                    });
                }
            },
            error: function (xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error fetching categories: ' + xhr.statusText,
                });
            }
        });
    }

    // Render categories in the table
    function renderCategories(categories, currentPage) {
        categoriesTableBody.empty();
        categories.forEach((category, index) => {
            const isChecked = selectedCategories.includes(category.id.toString()) ? 'checked' : '';
            const row = `
                <tr>
                    <td><input type="checkbox" class="select-item" value="${category.id}" ${isChecked}></td>
                    <td>${(currentPage - 1) * perPage + index + 1}</td>
                    <td>${category.name}</td>
                    <td>${category.parent ? category.parent.name : 'No Parent'}</td>
                    <td><button class="btn btn-sm btn-primary edit-btn" data-id="${category.id}">Edit</button></td>
                    <td><button class="btn btn-sm btn-danger delete-btn" data-id="${category.id}">Delete</button></td>
                </tr>
            `;
            categoriesTableBody.append(row);
        });

        const allSelected = selectedCategories.length === totalCategories && totalCategories > 0;
        selectAllCheckbox.prop('checked', allSelected);

        $('.select-item').off('change').on('change', function () {
            const id = $(this).val();
            if ($(this).is(':checked')) {
                if (!selectedCategories.includes(id)) {
                    selectedCategories.push(id);
                }
            } else {
                selectedCategories = selectedCategories.filter(catId => catId !== id);
                allCategoriesSelected = false;
                if (selectedCategories.length + 1 === totalCategories) {
                    Swal.fire({
                        icon: 'info',
                        title: 'Deselected',
                        text: 'All categories have been deselected.',
                    });
                }
            }
            deleteSelectedBtn.prop('disabled', selectedCategories.length === 0);
            selectAllCheckbox.prop('checked', selectedCategories.length === totalCategories && totalCategories > 0);
        });

        $('.edit-btn').off('click').on('click', function () {
            const id = $(this).data('id');
            showCategory(id);
        });

        $('.delete-btn').off('click').on('click', function () {
            const id = $(this).data('id');
            $('#delete-id').val(id);
            deleteModal.modal('show');
        });
    }

    // Render pagination links
    function renderPagination(total, perPage) {
        const totalPages = Math.ceil(total / perPage);
        paginationContainer.empty();

        if (totalPages <= 1) return;

        const startItem = (currentPage - 1) * perPage + 1;
        const endItem = Math.min(startItem + (perPage - 1), total);
        const summary = `<div class="pagination-summary mb-2">
            Show ${startItem} to ${endItem} of ${total} category
        </div>`;
        paginationContainer.append(summary);

        let pagination = '<ul class="pagination">';
        pagination += `<li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" data-page="${currentPage - 1}">Previous</a>
        </li>`;

        let startPage = Math.max(currentPage - 3, 1);
        let endPage = Math.min(currentPage + 3, totalPages);

        if (startPage > 1) {
            pagination += `<li class="page-item">
                <a class="page-link" href="#" data-page="1">1</a>
            </li>`;
            if (startPage > 2) {
                pagination += `<li class="page-item disabled">
                    <span class="page-link">...</span>
                </li>`;
            }
        }

        for (let i = startPage; i <= endPage; i++) {
            pagination += `<li class="page-item ${i === currentPage ? 'active' : ''}">
                <a class="page-link" href="#" data-page="${i}">${i}</a>
            </li>`;
        }

        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                pagination += `<li class="page-item disabled">
                    <span class="page-link">...</span>
                </li>`;
            }
            pagination += `<li class="page-item">
                <a class="page-link" href="#" data-page="${totalPages}">${totalPages}</a>
            </li>`;
        }

        pagination += `<li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
            <a class="page-link" href="#" data-page="${currentPage + 1}">Next</a>
        </li>`;
        pagination += '</ul>';
        paginationContainer.append(pagination);

        $('.page-link').off('click').on('click', function (e) {
            e.preventDefault();
            const page = $(this).data('page');
            if (page && page >= 1 && page <= totalPages) {
                currentPage = page;
                loadCategories();
            }
        });
    }

    // Load parent categories for filter and modal
    function loadParentCategories(selectedId = null, parentId = null) {
        $.ajax({
            url: '/api/categories?list&parent_only',
            method: 'GET',
            success: function (response) {
                if (response.status) {
                    const parentFilterSelect = $('#parent-filter');
                    parentFilterSelect.empty().append('<option value="">All Categories</option>');
                    response.data.forEach(category => {
                        if (category.id !== selectedId) {
                            parentFilterSelect.append(`<option value="${category.id}">${category.name}</option>`);
                        }
                    });
                }
            },
            error: function (xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error fetching parent categories: ' + xhr.statusText,
                });
            }
        });

        // Load all categories for parentSelect
        $.ajax({
            url: '/api/categories?list',
            method: 'GET',
            success: function (response) {
                if (response.status) {
                    const parentSelect = $('#parent_id');
                    parentSelect.empty().append('<option value="">No Parent</option>');
                    response.data.forEach(category => {
                        if (category.id !== selectedId) {
                            parentSelect.append(`<option value="${category.id}" ${category.id === parentId ? 'selected' : ''}>${category.name}</option>`);
                        }
                    });
                }
            },
            error: function (xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error fetching all categories for parent select: ' + xhr.statusText,
                });
            }
        });

    }

    // Show category details for editing
    function showCategory(id) {
        $.ajax({
            url: `/api/categories/${id}`,
            method: 'GET',
            success: function (response) {
                if (response.status) {
                    const category = response.data;
                    const parentId = category.parent ? category.parent.id : null;
                    $('#categoryId').val(category.id);
                    $('#name').val(category.name);
                    $('#parent_id').val(parentId);
                    categoryModalLabel.text('Edit category');
                    loadParentCategories(category.id, parentId);
                    categoryModal.modal('show');
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to fetch category: ' + (response.error || 'Unknown error'),
                    });
                }
            },
            error: function (xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error fetching category: ' + xhr.statusText,
                });
            }
        });
    }

    // Save category (add or update)
    function saveCategory() {
        const id = $('#categoryId').val();
        const url = id ? `/api/categories/${id}` : '/api/categories';
        const method = id ? 'PUT' : 'POST';
        const data = {
            name: $('#name').val(),
            parent_id: $('#parent_id').val() || null
        };

        $.ajax({
            url: url,
            method: method,
            data: JSON.stringify(data),
            contentType: 'application/json',
            success: function (response) {
                if (response.status) {
                    categoryModal.modal('hide');
                    loadCategories();
                    loadTree();
                    loadParentCategories();
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: response.message,
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to save category: ' + (response.error || 'Unknown error'),
                    });
                }
            },
            error: function (xhr) {
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON.errors;
                    let errorMessage = '';
                    console.log(errors);
                    for (const key in errors) {
                        if (errors.hasOwnProperty(key)) {
                            errorMessage += errors[key].join(', ') + '\n';
                        }
                    }
                    Swal.fire({
                        icon: 'error',
                        title: 'Validation Error',
                        text: errorMessage,
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error saving category: ' + xhr.statusText,
                    });
                }
            }
        });
    }

    // Delete a single category
    function deleteCategory(id) {
        $.ajax({
            url: `/api/categories/${id}`,
            method: 'DELETE',
            success: function (response) {
                if (response.status) {
                    selectedCategories = selectedCategories.filter(catId => catId !== id.toString());
                    allCategoriesSelected = false;
                    deleteSelectedBtn.prop('disabled', selectedCategories.length === 0);
                    selectAllCheckbox.prop('checked', false);
                    deleteModal.modal('hide');
                    loadCategories();
                    loadTree();
                    loadParentCategories();
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: response.message,
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to delete category: ' + (response.error || 'Unknown error'),
                    });
                }
            },
            error: function (xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error deleting category: ' + xhr.statusText,
                });
            }
        });
    }

    // Delete multiple categories
    function deleteMultipleCategories() {
        if (selectedCategories.length === 0) return;
        Swal.fire({
            title: 'Are you sure?',
            text: 'Do you want to delete the selected categories?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete',
            cancelButtonText: 'Cancel',
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '/api/categories-api/delete-multiple',
                    method: 'POST',
                    data: JSON.stringify({ ids: selectedCategories }),
                    contentType: 'application/json',
                    success: function (response) {
                        if (response.status) {
                            selectedCategories = [];
                            allCategoriesSelected = false;
                            deleteSelectedBtn.prop('disabled', true);
                            selectAllCheckbox.prop('checked', false);
                            loadCategories();
                            loadTree();
                            loadParentCategories();
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: response.message,
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Failed to delete categories: ' + (response.error || 'Unknown error'),
                            });
                        }
                    },
                    error: function (xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Error deleting categories: ' + xhr.statusText,
                        });
                    }
                });
            }
        });
    }

    // Load and render categories tree
    function loadTree() {
        $.ajax({
            url: '/api/categories-api/tree',
            method: 'GET',
            success: function (response) {
                if (response.status) {
                    renderTree(response.data, categoryTree);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to fetch category tree: ' + (response.error || 'Unknown error'),
                    });
                }
            },
            error: function (xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error fetching category tree: ' + xhr.statusText,
                });
            }
        });
    }

    // Render tree recursively
    function renderTree(categories, parentUl) {
        parentUl.empty();
        categories.forEach(category => {
            const li = $('<li>').text(category.name).addClass('toggle');
            if (category.childrenRecursive && category.childrenRecursive.length > 0) {
                li.addClass('collapsed');
                const ul = $('<ul>');
                renderTree(category.childrenRecursive, ul);
                li.append(ul);
            }
            parentUl.append(li);
        });

        $('.toggle').off('click').on('click', function (e) {
            e.stopPropagation();
            const $this = $(this);
            const ul = $this.find('> ul');
            if (ul.length) {
                ul.toggleClass('show');
                $this.toggleClass('collapsed');
            }
        });
    }

    // Handle search input
    searchInput.on('input', function () {
        keyword = $(this).val().trim();
        currentPage = 1;
        allCategoriesSelected = false;
        loadCategories();
    });

    // Handle parent filter
    parentFilter.on('change', function () {
        parentId = $(this).val();
        currentPage = 1;
        allCategoriesSelected = false;
        loadCategories();
    });

    // Handle select all checkbox
    selectAllCheckbox.on('change', function () {
        const isChecked = $(this).is(':checked');
        if (isChecked) {
            allCategoriesSelected = true;
            $('.select-item').each(function () {
                const id = $(this).val();
                if (!selectedCategories.includes(id)) {
                    selectedCategories.push(id);
                }
                $(this).prop('checked', true);
            });
            deleteSelectedBtn.prop('disabled', false);
            $.ajax({
                url: `/api/categories?list=1&keyword=${encodeURIComponent(keyword)}${parentId ? '&parent_id=' + parentId : ''}`,
                method: 'GET',
                success: function (response) {
                    if (response.status) {
                        const allIds = response.data.map(category => category.id.toString());
                        selectedCategories = [...new Set([...selectedCategories, ...allIds])];
                        selectAllCheckbox.prop('checked', selectedCategories.length === totalCategories && totalCategories > 0);
                    }
                },
                error: function (xhr) {
                    console.error('Error fetching all categories:', xhr.statusText);
                }
            });
        } else {
            allCategoriesSelected = false;
            selectedCategories = [];
            $('.select-item').prop('checked', false);
            deleteSelectedBtn.prop('disabled', true);
        }
    });

    // Handle add category button
    addCategoryBtn.on('click', function () {
        categoryForm[0].reset();
        $('#categoryId').val('');
        categoryModalLabel.text('Add Category');
        loadParentCategories();
    });

    // Handle save category button
    saveCategoryBtn.on('click', function () {
        if (categoryForm[0].checkValidity()) {
            saveCategory();
        } else {
            categoryForm[0].reportValidity();
        }
    });

    // Handle confirm delete button
    confirmDeleteBtn.on('click', function () {
        const id = $('#delete-id').val();
        deleteCategory(id);
    });

    // Handle delete selected button
    deleteSelectedBtn.on('click', deleteMultipleCategories);

    // Show Add Modal
    $('#addCategoryBtn').on('click', function () {
        $('#categoryId').val('');
        $('#name').val('');
        $('#parent_id').val('');
        $('#categoryModalLabel').text('Add Category');
        loadParentCategories();
        $('#categoryModal').modal('show');
    });

    // Initial load
    loadCategories();
    loadTree();
    loadParentCategories();
});
</script>
@endpush