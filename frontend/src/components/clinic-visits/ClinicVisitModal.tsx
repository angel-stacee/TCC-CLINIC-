import type { FormEventHandler } from "react";
import { Pill, Search, X, Phone, AlertTriangle, MessageSquare } from "lucide-react";
import type { VisitRecord } from "./ClinicVisitTable";

interface PatientOption {
    id: string | number;
    type: "student" | "faculty" | "staff" | string;
    label: string;
    identifier: string;
    name: string;
    contact_number?: string;
}

interface ClinicVisitModalProps {
    showForm: boolean;
    closeForm: () => void;
    editingVisit: VisitRecord | null;
    formData: any;
    handleChange: (event: React.ChangeEvent<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>) => void;
    handleSubmit: FormEventHandler<HTMLFormElement>;
    loading: boolean;
    patientSearch: string;
    setPatientSearch: (value: string) => void;
    showPatientDropdown: boolean;
    setShowPatientDropdown: (value: boolean) => void;
    filteredPatients: PatientOption[];
    selectPatient: (patient: PatientOption) => void;
    nurses: any[];
    getNurseName: (nurse: any) => string;
    getToday: () => string;
    setFormData: React.Dispatch<React.SetStateAction<any>>;
    handleTreatmentChange: (event: React.ChangeEvent<HTMLSelectElement>) => void;
    treatments: string[];
    treatmentNeedsMedicine: (treatment: string) => boolean;
    medicineSearch: string;
    setMedicineSearch: (value: string) => void;
    showMedicineDropdown: boolean;
    setShowMedicineDropdown: (value: boolean) => void;
    filteredMedicines: any[];
    selectMedicine: (medicine: any) => void;
    getMedicineName: (medicine: any) => string;
    getMedicineUnit: (medicine: any) => string;
    getMedicineStock: (medicine: any) => number;
    getMedicineTreatment: (medicine: any) => string;
    selectedMedicine: any;
}

export function ClinicVisitModal({
    showForm,
    closeForm,
    editingVisit,
    formData,
    handleChange,
    handleSubmit,
    loading,
    patientSearch,
    setPatientSearch,
    showPatientDropdown,
    setShowPatientDropdown,
    filteredPatients,
    selectPatient,
    nurses,
    getNurseName,
    getToday,
    setFormData,
    handleTreatmentChange,
    treatments,
    treatmentNeedsMedicine,
    medicineSearch,
    setMedicineSearch,
    showMedicineDropdown,
    setShowMedicineDropdown,
    filteredMedicines,
    selectMedicine,
    getMedicineName,
    getMedicineUnit,
    getMedicineStock,
    getMedicineTreatment,
    selectedMedicine,
}: ClinicVisitModalProps) {
    if (!showForm) return null;

    return (
        <div
            className="fixed inset-0 z-[90] flex items-center justify-center bg-black/40 p-4"
            onMouseDown={(event) => {
                if (event.target === event.currentTarget && !loading) {
                    closeForm();
                }
            }}
        >
            <div
                role="dialog"
                aria-modal="true"
                aria-labelledby="visit-form-modal-title"
                className="max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-2xl bg-white shadow-xl dark:bg-stone-900 border border-stone-200 dark:border-stone-800"
            >
                {/* Modal Header */}
                <div className="sticky top-0 z-20 flex items-center justify-between border-b bg-white px-6 py-4 dark:border-stone-800 dark:bg-stone-900">
                    <div>
                        <h2
                            id="visit-form-modal-title"
                            className="text-lg font-semibold text-stone-800 dark:text-stone-100"
                        >
                            {editingVisit ? "Edit Clinic Visit" : "Add New Clinic Visit"}
                        </h2>
                    </div>

                    <button
                        type="button"
                        onClick={closeForm}
                        disabled={loading}
                        aria-label="Close modal"
                        className="rounded-lg px-2.5 py-1 text-xl text-stone-500 hover:bg-stone-100 hover:text-stone-700 transition dark:hover:bg-stone-800 dark:hover:text-stone-300"
                    >
                        ×
                    </button>
                </div>

                {/* Modal Form */}
                <form onSubmit={handleSubmit} className="p-6">
                    <div className="grid gap-4 md:grid-cols-2">
                        {/* 1. Patient Selection (Left) */}
                        <div className="relative flex flex-col gap-1">
                            <label className="text-sm font-medium text-stone-700 dark:text-stone-300">
                                Patient
                                <span className="ml-1 text-red-500">*</span>
                            </label>

                            <div className="relative">
                                <Search
                                    size={16}
                                    className="absolute left-3 top-1/2 -translate-y-1/2 text-stone-400"
                                />

                                <input
                                    type="text"
                                    value={patientSearch}
                                    onChange={(event) => {
                                        const nextValue = event.target.value;
                                        setPatientSearch(nextValue);
                                        setShowPatientDropdown(true);

                                        if (!nextValue.trim()) {
                                            setFormData((prev: any) => ({
                                                ...prev,
                                                student_id: "",
                                                faculty_id: "",
                                                staff_id: "",
                                            }));
                                        }
                                    }}
                                    onFocus={() => setShowPatientDropdown(true)}
                                    placeholder="Search student, faculty, or staff..."
                                    className="w-full rounded-lg border border-stone-300 bg-white py-2.5 pl-9 pr-8 text-sm outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-100 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100"
                                />

                                {patientSearch && (
                                    <button
                                        type="button"
                                        onClick={() => {
                                            setPatientSearch("");
                                            setFormData((prev: any) => ({
                                                ...prev,
                                                student_id: "",
                                                faculty_id: "",
                                                staff_id: "",
                                            }));
                                        }}
                                        aria-label="Clear patient search"
                                        className="absolute right-2.5 top-1/2 flex h-5 w-5 -translate-y-1/2 items-center justify-center rounded text-stone-400 hover:text-stone-700"
                                    >
                                        <X size={14} />
                                    </button>
                                )}
                            </div>

                            {showPatientDropdown && (
                                <div className="absolute left-0 right-0 top-full z-50 mt-1 max-h-60 overflow-y-auto rounded-xl border border-stone-200 bg-white shadow-xl dark:border-stone-700 dark:bg-stone-800">
                                    {filteredPatients.length === 0 ? (
                                        <div className="px-4 py-5 text-center text-xs text-stone-500">
                                            No patient found.
                                        </div>
                                    ) : (
                                        filteredPatients.map((patient) => (
                                            <button
                                                key={`${patient.type}-${patient.id}`}
                                                type="button"
                                                onClick={() => selectPatient(patient)}
                                                className="w-full border-b border-stone-100 px-4 py-2.5 text-left transition last:border-b-0 hover:bg-stone-50 dark:border-stone-700/60 dark:hover:bg-stone-700/50"
                                            >
                                                <div className="flex items-center justify-between gap-3">
                                                    <div>
                                                        <p className="text-xs font-bold text-stone-800 dark:text-stone-200">
                                                            {patient.name}
                                                        </p>
                                                        <p className="mt-0.5 text-[10px] text-stone-500 dark:text-stone-400">
                                                            {patient.identifier}
                                                        </p>
                                                    </div>

                                                    <span
                                                        className={`rounded-full px-2 py-0.5 text-[9px] font-bold ${
                                                            patient.type === "student"
                                                                ? "bg-amber-50 text-amber-600 dark:bg-amber-950/40 dark:text-amber-400"
                                                                : patient.type === "faculty"
                                                                  ? "bg-amber-50 text-amber-600 dark:bg-amber-950/40 dark:text-amber-400"
                                                                  : "bg-green-50 text-green-600 dark:bg-green-950/40 dark:text-green-400"
                                                        }`}
                                                    >
                                                        {patient.label}
                                                    </span>
                                                </div>
                                            </button>
                                        ))
                                    )}
                                </div>
                            )}
                        </div>

                        {/* Cellphone Number (Right) */}
                        <div className="flex flex-col gap-1">
                            <label className="flex items-center gap-1.5 text-sm font-medium text-stone-700 dark:text-stone-300">
                                <Phone size={14} className="text-stone-400" />
                                Cellphone Number
                                <span className="ml-1 text-red-500">*</span>
                            </label>

                            <input
                                type="tel"
                                name="contact_number"
                                value={formData.contact_number || ""}
                                onChange={handleChange}
                                placeholder="e.g. 09123456789"
                                required
                                className="w-full rounded-lg border border-stone-300 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-100 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100"
                            />
                        </div>

                        {/* SMS & Emergency Options (Full width across md:col-span-2) */}
                        <div className="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-stone-200 bg-stone-50/80 p-3 dark:border-stone-800 dark:bg-stone-800/40 md:col-span-2">
                            <div className="flex flex-wrap items-center gap-4 text-xs">
                                <label className="inline-flex cursor-pointer items-center gap-2 font-medium text-stone-700 dark:text-stone-300">
                                    <input
                                        type="checkbox"
                                        name="send_sms"
                                        checked={formData.send_sms !== false}
                                        onChange={(e) =>
                                            setFormData((prev: any) => ({
                                                ...prev,
                                                send_sms: e.target.checked,
                                            }))
                                        }
                                        className="h-4 w-4 rounded border-stone-300 text-[#70091f] focus:ring-[#70091f]"
                                    />
                                    <span className="flex items-center gap-1.5">
                                        <MessageSquare size={14} className="text-[#70091f]" />
                                        Send SMS Notification to Student/Guardian
                                    </span>
                                </label>

                                <label className="inline-flex cursor-pointer items-center gap-2 font-bold text-red-600 dark:text-red-400">
                                    <input
                                        type="checkbox"
                                        name="is_emergency"
                                        checked={Boolean(formData.is_emergency)}
                                        onChange={(e) =>
                                            setFormData((prev: any) => ({
                                                ...prev,
                                                is_emergency: e.target.checked,
                                            }))
                                        }
                                        className="h-4 w-4 rounded border-red-300 text-red-600 focus:ring-red-500"
                                    />
                                    <span className="flex items-center gap-1">
                                        <AlertTriangle size={14} />
                                        Mark as Emergency Case
                                    </span>
                                </label>
                            </div>

                            {formData.is_emergency && (
                                <span className="inline-flex items-center gap-1 rounded-full bg-red-100 px-2.5 py-1 text-[11px] font-bold text-red-700 dark:bg-red-950/60 dark:text-red-400">
                                    🚨 Emergency SMS Alert Active
                                </span>
                            )}
                        </div>

                        {/* 2. Nurse on Duty (Left) */}
                        <div className="flex flex-col gap-1">
                            <label className="text-sm font-medium text-stone-700 dark:text-stone-300">
                                Nurse on Duty
                                <span className="ml-1 text-red-500">*</span>
                            </label>

                            <select
                                name="nurse_id"
                                value={formData.nurse_id}
                                onChange={handleChange}
                                required
                                className="w-full rounded-lg border border-stone-300 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-100 disabled:cursor-not-allowed disabled:bg-stone-100 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100"
                            >
                                <option value="">Select Nurse on Duty</option>
                                {nurses.map((nurse) => (
                                    <option key={nurse.id} value={nurse.id}>
                                        {getNurseName(nurse)}
                                    </option>
                                ))}
                            </select>
                        </div>

                        {/* 3. Visit Date (Left) */}
                        <div className="flex flex-col gap-1">
                            <label className="text-sm font-medium text-stone-700 dark:text-stone-300">
                                Visit Date
                                <span className="ml-1 text-red-500">*</span>
                            </label>

                            <input
                                type="date"
                                name="visit_date"
                                value={formData.visit_date}
                                onChange={handleChange}
                                min={!editingVisit ? getToday() : undefined}
                                required
                                className="w-full rounded-lg border border-stone-300 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-100 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100"
                            />
                        </div>

                        {/* 4. Reason for Visit (Right) */}
                        <div className="flex flex-col gap-1">
                            <label className="text-sm font-medium text-stone-700 dark:text-stone-300">
                                Reason for Visit
                                <span className="ml-1 text-red-500">*</span>
                            </label>

                            <select
                                value={formData.reason}
                                onChange={(event) =>
                                    setFormData((previous: any) => ({
                                        ...previous,
                                        reason: event.target.value,
                                    }))
                                }
                                className="w-full rounded-lg border border-stone-300 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-100 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100"
                                required
                            >
                                <option value="">Select Reason</option>
                                <option value="Fever">Fever</option>
                                <option value="Headache">Headache</option>
                                <option value="Stomachache">Stomachache</option>
                                <option value="Cough / Cold">Cough / Cold</option>
                                <option value="Dizziness">Dizziness</option>
                                <option value="Injury / Wound">Injury / Wound</option>
                                <option value="Body Pain">Body Pain</option>
                                <option value="Check-up">Check-up</option>
                                <option value="First Aid">First Aid</option>
                                <option value="Medication">Medication</option>
                                <option value="Medical Concern">Medical Concern</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>

                        {/* 5. Temperature (Left) */}
                        <div className="flex flex-col gap-1">
                            <label className="text-sm font-medium text-stone-700 dark:text-stone-300">
                                Temperature
                            </label>
                            <input
                                type="text"
                                name="temperature"
                                value={formData.temperature}
                                onChange={handleChange}
                                placeholder="e.g. 36.5 °C"
                                className="w-full rounded-lg border border-stone-300 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-100 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100"
                            />
                        </div>

                        {/* 6. Blood Pressure (Right) */}
                        <div className="flex flex-col gap-1">
                            <label className="text-sm font-medium text-stone-700 dark:text-stone-300">
                                Blood Pressure
                            </label>
                            <input
                                type="text"
                                name="blood_pressure"
                                value={formData.blood_pressure}
                                onChange={handleChange}
                                placeholder="e.g. 120/80"
                                className="w-full rounded-lg border border-stone-300 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-100 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100"
                            />
                        </div>

                        {/* 7. Symptoms (Left) */}
                        <div className="flex flex-col gap-1">
                            <label className="text-sm font-medium text-stone-700 dark:text-stone-300">
                                Symptoms
                            </label>
                            <textarea
                                name="symptoms"
                                value={formData.symptoms}
                                onChange={handleChange}
                                rows={2}
                                placeholder="Describe the symptoms..."
                                className="w-full resize-none rounded-lg border border-stone-300 bg-white p-2.5 text-sm outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-100 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100"
                            />
                        </div>

                        {/* 8. Assessment (Right) */}
                        <div className="flex flex-col gap-1">
                            <label className="text-sm font-medium text-stone-700 dark:text-stone-300">
                                Assessment
                            </label>
                            <textarea
                                name="assessment"
                                value={formData.assessment}
                                onChange={handleChange}
                                rows={2}
                                placeholder="Clinic assessment..."
                                className="w-full resize-none rounded-lg border border-stone-300 bg-white p-2.5 text-sm outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-100 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100"
                            />
                        </div>

                        {/* 9. Treatment (Left) */}
                        <div className="flex flex-col gap-1">
                            <label className="text-sm font-medium text-stone-700 dark:text-stone-300">
                                Treatment
                            </label>

                            <select
                                name="treatment"
                                value={formData.treatment}
                                onChange={handleTreatmentChange}
                                className="w-full rounded-lg border border-stone-300 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-100 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100"
                            >
                                <option value="">Select Treatment</option>
                                {treatments.map((treatment) => (
                                    <option key={treatment} value={treatment}>
                                        {treatment}
                                    </option>
                                ))}
                            </select>
                        </div>

                        {/* 10. Medicine / Supply (Full width) */}
                        <div className="relative flex flex-col gap-1 md:col-span-2">
                            <div className="flex items-center justify-between">
                                <label className="flex items-center gap-1.5 text-sm font-medium text-stone-700 dark:text-stone-300">
                                    <Pill size={15} />
                                    Medicine / Supply
                                    {treatmentNeedsMedicine(formData.treatment) ? (
                                        <span className="text-red-500">*</span>
                                    ) : (
                                        <span className="text-stone-400 font-normal text-xs">(Optional)</span>
                                    )}
                                </label>

                                {formData.medicine_id && (
                                    <button
                                        type="button"
                                        onClick={() => {
                                            setFormData((previous: any) => ({
                                                ...previous,
                                                medicine_id: "",
                                                medicine_quantity: "",
                                            }));
                                            setMedicineSearch("");
                                        }}
                                        className="text-xs font-medium text-red-600 hover:underline"
                                    >
                                        Clear
                                    </button>
                                )}
                            </div>

                            <div className="relative">
                                <Search
                                    size={16}
                                    className="absolute left-3 top-1/2 -translate-y-1/2 text-stone-400"
                                />

                                <input
                                    type="text"
                                    value={medicineSearch}
                                    onChange={(event) => {
                                        setMedicineSearch(event.target.value);
                                        setFormData((previous: any) => ({
                                            ...previous,
                                            medicine_id: "",
                                            medicine_quantity: "",
                                        }));
                                        setShowMedicineDropdown(true);
                                    }}
                                    onFocus={() => setShowMedicineDropdown(true)}
                                    placeholder="Search medicine or supply..."
                                    className="w-full rounded-lg border border-stone-300 bg-white py-2.5 pl-9 pr-3 text-sm outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-100 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100"
                                />
                            </div>

                            {showMedicineDropdown && (
                                <div className="absolute left-0 right-0 top-full z-50 mt-1 max-h-60 overflow-y-auto rounded-xl border border-stone-200 bg-white shadow-xl dark:border-stone-700 dark:bg-stone-800">
                                    {filteredMedicines.length === 0 ? (
                                        <div className="px-4 py-5 text-center text-xs text-stone-500">
                                            No medicine found.
                                        </div>
                                    ) : (
                                        filteredMedicines.map((medicine) => {
                                            const name = getMedicineName(medicine);
                                            const unit = getMedicineUnit(medicine);
                                            const stock = getMedicineStock(medicine);
                                            const treatment = getMedicineTreatment(medicine);
                                            const outOfStock = stock <= 0;
                                            const isCurrentlySelected = String(formData.medicine_id) === String(medicine.id);
                                            const disableMedicine = outOfStock && !isCurrentlySelected;

                                            return (
                                                <button
                                                    key={medicine.id}
                                                    type="button"
                                                    disabled={disableMedicine}
                                                    onClick={() => selectMedicine(medicine)}
                                                    className="w-full border-b border-stone-100 px-4 py-2.5 text-left transition last:border-b-0 hover:bg-stone-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-stone-700/60 dark:hover:bg-stone-700/50"
                                                >
                                                    <div className="flex items-center justify-between gap-3">
                                                        <div className="min-w-0">
                                                            <p className="text-xs font-bold text-stone-800 dark:text-stone-200">
                                                                {name}
                                                            </p>
                                                            <p className="mt-0.5 text-[10px] text-stone-500 dark:text-stone-400">
                                                                Unit: {unit} · Treatment: {treatment}
                                                            </p>
                                                        </div>
                                                        <span
                                                            className={`shrink-0 rounded-full px-2 py-0.5 text-[10px] font-bold ${
                                                                stock > 10
                                                                    ? "bg-green-50 text-green-600 dark:bg-green-950/40 dark:text-green-400"
                                                                    : stock > 0
                                                                      ? "bg-amber-50 text-amber-600 dark:bg-amber-950/40 dark:text-amber-400"
                                                                      : "bg-red-50 text-red-600 dark:bg-red-950/40 dark:text-red-400"
                                                            }`}
                                                        >
                                                            {stock > 0 ? `${stock} left` : "Out of stock"}
                                                        </span>
                                                    </div>
                                                </button>
                                            );
                                        })
                                    )}
                                </div>
                            )}

                            {selectedMedicine && (
                                <div className="mt-2 rounded-lg border border-amber-200 bg-amber-50/50 p-2.5 dark:border-amber-900/40 dark:bg-amber-950/20">
                                    <div className="flex items-center justify-between gap-2">
                                        <div>
                                            <p className="text-xs font-bold text-stone-800 dark:text-stone-200">
                                                {getMedicineName(selectedMedicine)}
                                            </p>
                                            <p className="text-[10px] text-stone-500 dark:text-stone-400">
                                                Available: {getMedicineStock(selectedMedicine)} {getMedicineUnit(selectedMedicine)}
                                            </p>
                                        </div>
                                    </div>

                                    <div className="mt-2">
                                        <label className="text-xs font-medium text-stone-700 dark:text-stone-300">
                                            Quantity Used
                                            <span className="ml-1 text-red-500">*</span>
                                        </label>
                                        <input
                                            type="number"
                                            name="medicine_quantity"
                                            min="1"
                                            max={getMedicineStock(selectedMedicine)}
                                            value={formData.medicine_quantity}
                                            onChange={handleChange}
                                            required
                                            placeholder="Enter quantity used"
                                            className="mt-1 w-full rounded-lg border border-stone-300 bg-white px-3 py-1.5 text-xs outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-100 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100"
                                        />
                                    </div>
                                </div>
                            )}
                        </div>

                        {/* 11. Remarks (Full width md:col-span-2) */}
                        <div className="flex flex-col gap-1 md:col-span-2">
                            <label className="text-sm font-medium text-stone-700 dark:text-stone-300">
                                Remarks
                            </label>
                            <textarea
                                name="remarks"
                                value={formData.remarks}
                                onChange={handleChange}
                                rows={2}
                                placeholder="Additional remarks..."
                                className="w-full resize-none rounded-lg border border-stone-300 bg-white p-2.5 text-sm outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-100 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100"
                            />
                        </div>
                    </div>

                    {/* Divider and Action Buttons matching reference */}
                    <div className="mt-6 flex items-center justify-end gap-3 border-t border-stone-200 pt-4 dark:border-stone-800">
                        <button
                            type="button"
                            onClick={closeForm}
                            disabled={loading}
                            className="rounded-lg border border-stone-300 bg-white px-4 py-2 text-sm font-medium text-stone-700 shadow-sm transition hover:bg-stone-50 disabled:opacity-50 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-300 dark:hover:bg-stone-700"
                        >
                            Cancel
                        </button>

                        <button
                            type="submit"
                            disabled={loading}
                            className="flex items-center gap-2 rounded-lg bg-[#d96502] px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-[#b85400] disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            {loading
                                ? "Saving..."
                                : editingVisit
                                ? "Update Visit"
                                : "Save Visit"}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}

