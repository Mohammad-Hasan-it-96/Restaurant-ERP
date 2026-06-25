@extends('layouts.app')

@section('title', 'إدارة الخيارات')

@section('content')
<div class="container-fluid">

    <div class="d-flex flex-column flex-md-row justify-content-md-between align-items-md-center mb-4 gap-2">
        <div>
            <h3 class="mb-1" style="color: var(--text);">
                <i class="bi bi-sliders me-2" style="color: var(--primary);"></i>
                إدارة الخيارات
            </h3>
        </div>
        @if(Auth::user()->role === 'admin' || Auth::user()->role === 'moderator')
        <a href="{{ route('admin.options.create') }}" class="btn btn-primary d-flex align-items-center gap-2">
            <i class="bi bi-plus-lg"></i> إضافة خيار
        </a>
        @endif
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card shadow-sm mb-4" style="border: 1px solid var(--border-color); background-color: var(--card-bg);">
        <div class="card-body">
            <form action="{{ route('admin.options.index') }}" method="GET" class="row g-3">
                <div class="col-md-8">
                    <input type="text" name="search" class="form-control"
                           placeholder="بحث باسم الخيار..."
                           value="{{ request('search') }}">
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-fill">
                        <i class="bi bi-search me-1"></i>بحث
                    </button>
                    <a href="{{ route('admin.options.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-x-lg"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm" style="border: 1px solid var(--border-color); background-color: var(--card-bg);">
        <div class="card-body p-0">
            @if($options->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-sliders" style="font-size: 3rem; color: var(--text-muted);"></i>
                    <p class="mt-3 text-muted">لا توجد خيارات بعد.</p>
                    @if(Auth::user()->role === 'admin' || Auth::user()->role === 'moderator')
                    <a href="{{ route('admin.options.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-lg me-1"></i>إضافة خيار
                    </a>
                    @endif
                </div>
            @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead style="background-color: var(--sidebar-hover);">
                        <tr>
                            <th class="px-4 py-3" style="color: var(--text-muted); font-size: .8rem; font-weight: 600; text-transform: uppercase;">#</th>
                            <th class="py-3" style="color: var(--text-muted); font-size: .8rem; font-weight: 600; text-transform: uppercase;">اسم الخيار</th>
                            <th class="py-3" style="color: var(--text-muted); font-size: .8rem; font-weight: 600; text-transform: uppercase;">القيم</th>
                            <th class="py-3" style="color: var(--text-muted); font-size: .8rem; font-weight: 600; text-transform: uppercase;">الحالة</th>
                            <th class="py-3 text-end pe-4" style="color: var(--text-muted); font-size: .8rem; font-weight: 600; text-transform: uppercase;">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($options as $option)
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td class="px-4 py-3 text-muted" style="font-size: .875rem;">{{ $option->id }}</td>
                            <td class="py-3 fw-semibold" style="color: var(--text);">{{ $option->name }}</td>
                            <td class="py-3">
                                <span class="badge bg-primary-subtle text-primary rounded-pill px-3">
                                    {{ $option->values_count }} قيمة
                                </span>
                            </td>
                            <td class="py-3">
                                @if($option->is_active)
                                    <span class="badge bg-success-subtle text-success rounded-pill px-3">
                                        <i class="bi bi-check-circle me-1"></i>مفعّل
                                    </span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger rounded-pill px-3">
                                        <i class="bi bi-x-circle me-1"></i>معطّل
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 text-end pe-4">
                                @if(Auth::user()->role === 'admin' || Auth::user()->role === 'moderator')
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="{{ route('admin.options.edit', $option) }}"
                                       class="btn btn-sm btn-outline-primary" title="تعديل">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button type="button"
                                            class="btn btn-sm btn-outline-danger delete-option"
                                            data-id="{{ $option->id }}"
                                            data-name="{{ $option->name }}"
                                            data-url="{{ route('admin.options.destroy', $option) }}">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($options->hasPages())
            <div class="d-flex justify-content-between align-items-center px-4 py-3" style="border-top: 1px solid var(--border-color);">
                <small class="text-muted">
                    {{ $options->firstItem() }} – {{ $options->lastItem() }} من {{ $options->total() }}
                </small>
                {{ $options->links() }}
            </div>
            @endif
            @endif
        </div>
    </div>

</div>
@endsection

<div class="modal fade" id="deleteOptionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title text-danger">
                    <i class="bi bi-exclamation-triangle me-2"></i>تأكيد الحذف
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-4 text-center">
                <i class="bi bi-trash3 text-danger mb-3 d-block" style="font-size:3rem;"></i>
                <p class="mb-0" id="deleteOptionMessage" style="color: var(--text);"></p>
            </div>
            <div class="modal-footer border-0 pt-0 justify-content-center gap-2">
                <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">إلغاء</button>
                <form id="deleteOptionForm" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger px-4">
                        <i class="bi bi-trash me-1"></i>حذف
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal  = new bootstrap.Modal(document.getElementById('deleteOptionModal'));
    const msgEl  = document.getElementById('deleteOptionMessage');
    const formEl = document.getElementById('deleteOptionForm');

    document.querySelectorAll('.delete-option').forEach(function (btn) {
        btn.addEventListener('click', function () {
            msgEl.textContent = 'هل أنت متأكد من حذف الخيار "' + this.dataset.name + '"؟ سيتم حذف جميع قيمه أيضاً.';
            formEl.action = this.dataset.url;
            modal.show();
        });
    });
});
</script>
@endpush
