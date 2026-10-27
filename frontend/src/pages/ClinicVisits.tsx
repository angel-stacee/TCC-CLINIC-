import { useCallback, useEffect, useMemo, useRef, useState } from "react";
import {
    Stethoscope,
    Pencil,
    Trash2,
    Search,
    UserRound,
    Activity,
    Plus,
    X,
    Pill,
    CalendarDays,
    CircleHelp,
    Phone,
    AlertTriangle,
    MessageSquare,
} from "lucide-react";
import api from "../services/api";
import { getCachedRecord } from "../services/recordCache";
import { ClinicVisitTable, type VisitRecord } from "../components/clinic-visits/ClinicVisitTable";
import { ClinicVisitModal } from "../components/clinic-visits/ClinicVisitModal";
import FloatingToast from "../components/common/FloatingToast";
import { addClinicNotification } from "../services/notifications";

const EMPTY_FORM = {
    student_id: "",
    faculty_id: "",
    staff_id: "",
    nurse_id: "",
    contact_number: "",
    is_emergency: false,
    send_sms: true,
    visit_date: "",
    reason: "",
    symptoms: "",
    temperature: "",
    blood_pressure: "",
    assessment: "",
    treatment: "",
    medicine_id: "",
    medicine_quantity: "",
    remarks: "",
};

const TREATMENTS = [
    "First Aid",
    "Medication",
    "Wound Care",
    "Cold Compress",
    "Hot Compress",
    "Observation",
    "Referral",
    "Other",
];

const normalizeVisitDateSearch = (value) => {
    const usDate = /^(\d{1,2})\/(\d{1,2})\/(\d{4})$/.exec(value);
    const isoDate = /^(\d{4})-(\d{1,2})-(\d{1,2})$/.exec(value);
    const parts = usDate
        ? [Number(usDate[3]), Number(usDate[1]), Number(usDate[2])]
        : isoDate
          ? [Number(isoDate[1]), Number(isoDate[2]), Number(isoDate[3])]
          : null;

    if (!parts) return "";

    const [year, month, day] = parts;
    const date = new Date(year, month - 1, day, 12);

    if (
        date.getFullYear() !== year ||
        date.getMonth() !== month - 1 ||
        date.getDate() !== day
    ) {
        return "";
    }

    return `${year}-${String(month).padStart(2, "0")}-${String(day).padStart(2, "0")}`;
};







const TREATMENTS_WITH_MEDICINE = [
    "First Aid",
    "Medication",
    "Wound Care",
    "Cold Compress",
    "Hot Compress",
];







const treatmentNeedsMedicine = (treatment: string) => {
    return TREATMENTS_WITH_MEDICINE.includes(treatment);
};

function ClinicVisits() {
    const [visits, setVisits] = useState<VisitRecord[]>([]);
    const [visitPagination, setVisitPagination] = useState({
        current_page: 1,
        last_page: 1,
        total: 0,
        per_page: 25,
    });
    const [students, setStudents] = useState([]);
    const [studentTotal, setStudentTotal] = useState(0);
    const [facultyStaffTotal, setFacultyStaffTotal] = useState(0);
    const [faculties, setFaculties] = useState([]);
    const [staff, setStaff] = useState([]);
    const [nurses, setNurses] = useState([]);
    const [medicines, setMedicines] = useState([]);

    const [showForm, setShowForm] = useState(false);
    const [showAddConfirmation, setShowAddConfirmation] = useState(false);
    const [pendingVisitPayload, setPendingVisitPayload] = useState(null);
    const [editingVisit, setEditingVisit] = useState(null);
    const [deleteVisit, setDeleteVisit] = useState<VisitRecord | null>(null);
    const [toastMessage, setToastMessage] = useState("");

    const [formData, setFormData] = useState({
        ...EMPTY_FORM,
    });

    const [loading, setLoading] = useState(false);
    const [pageLoading, setPageLoading] = useState(true);

    const [search, setSearch] = useState("");
    const [patientTypeFilter, setPatientTypeFilter] = useState("all");
    const [dateFilter, setDateFilter] = useState("all");
    const [treatmentFilter, setTreatmentFilter] = useState("all");
    const [sortBy, setSortBy] = useState("date_desc");

    const hasActiveFilters = Boolean(
        search.trim() ||
        patientTypeFilter !== "all" ||
        dateFilter !== "all" ||
        treatmentFilter !== "all" ||
        sortBy !== "date_desc"
    );

    const resetAllFilters = () => {
        setSearch("");
        setPatientTypeFilter("all");
        setDateFilter("all");
        setTreatmentFilter("all");
        setSortBy("date_desc");
    };

    const [patientSearch, setPatientSearch] = useState("");
    const [showPatientDropdown, setShowPatientDropdown] =
        useState(false);

    const [medicineSearch, setMedicineSearch] = useState("");
    const [showMedicineDropdown, setShowMedicineDropdown] =
        useState(false);







    const normalizeData = (data) => {
        if (Array.isArray(data)) {
            return data;
        }

        if (Array.isArray(data?.data)) {
            return data.data;
        }

        return [];
    };

    const getFullName = (person) => {
        if (!person) {
            return "Unknown";
        }

        return `${person.first_name || ""} ${
            person.middle_name || ""
        } ${person.last_name || ""}`
            .replace(/\s+/g, " ")
            .trim();
    };

    const getNurseName = (nurse) => {
        if (!nurse) {
            return "Unknown Nurse";
        }

        if (nurse.name) {
            return nurse.name;
        }

        return getFullName(nurse);
    };

    const getMedicineName = (medicine) => {
        if (!medicine) {
            return "";
        }

        return (
            medicine.medicine_name ||
            medicine.name ||
            medicine.medication_name ||
            medicine.medicine ||
            ""
        );
    };

    const getMedicineUnit = (medicine) => {
        if (!medicine) {
            return "";
        }

        return medicine.unit || "";
    };

    const getMedicineStock = (medicine) => {
        if (!medicine) {
            return 0;
        }

        return Number(medicine.stock || 0);
    };

    const getMedicineTreatment = (medicine) => {
        if (!medicine) {
            return "";
        }

        return String(
            medicine.treatment_type || ""
        ).trim();
    };

    const formatDate = (date) => {
        if (!date) {
            return "-";
        }

        const value = String(date).slice(0, 10);
        const parts = value.split("-");

        if (parts.length !== 3) {
            return value;
        }

        return `${parts[1]}/${parts[2]}/${parts[0]}`;
    };

    const formatDateInput = (date) => {
        if (!date) {
            return "";
        }

        return String(date).slice(0, 10);
    };

    const getToday = () => {
        const today = new Date();

        const year = today.getFullYear();

        const month = String(
            today.getMonth() + 1
        ).padStart(2, "0");

        const day = String(
            today.getDate()
        ).padStart(2, "0");

        return `${year}-${month}-${day}`;
    };

    const getErrorMessage = (
        error,
        fallback = "Something went wrong."
    ) => {
        const validationErrors =
            error.response?.data?.errors;

        if (validationErrors) {
            const messages = Object.values(
                validationErrors
            )
                .flat()
                .filter(Boolean);

            if (messages.length > 0) {
                return messages.join("\n");
            }
        }

        return (
            error.response?.data?.message ||
            fallback
        );
    };







    const getPatientName = (visit: any): { name: string; id: string; type: string; contact_number: string } => {
        const contact =
            visit.contact_number ||
            visit.student?.contact_number ||
            visit.faculty?.contact_number ||
            visit.staff?.contact_number ||
            "";

        if (visit.student) {
            return {
                name: getFullName(visit.student),
                id: visit.student.student_id || "",
                type: "Student",
                contact_number: contact,
            };
        }

        if (visit.faculty) {
            return {
                name: getFullName(visit.faculty),
                id: visit.faculty.employee_id || "",
                type: "Faculty",
                contact_number: contact,
            };
        }

        if (visit.staff) {
            return {
                name: getFullName(visit.staff),
                id: visit.staff.staff_id || "",
                type: "Staff",
                contact_number: contact,
            };
        }

        return {
            name: "Unknown Patient",
            id: "",
            type: "",
            contact_number: contact,
        };
    };

    const patientOptions = useMemo(() => {
        return [
            ...students.map((student) => ({
                id: student.id,
                type: "student",
                label: "Student",
                identifier: student.student_id || "",
                name: getFullName(student),
                contact_number: student.contact_number || "",
            })),

            ...faculties.map((faculty) => ({
                id: faculty.id,
                type: "faculty",
                label: "Faculty",
                identifier: faculty.employee_id || "",
                name: getFullName(faculty),
                contact_number: faculty.contact_number || "",
            })),

            ...staff.map((person) => ({
                id: person.id,
                type: "staff",
                label: "Staff",
                identifier: person.staff_id || "",
                name: getFullName(person),
                contact_number: person.contact_number || "",
            })),
        ];
    }, [students, faculties, staff]);

    const filteredPatients = useMemo(() => {
        const keyword = patientSearch
            .toLowerCase()
            .trim();

        if (!keyword) {
            return patientOptions;
        }

        const tokens = keyword.split(/[\s,]+/).filter((t) => t && t !== "-");

        return patientOptions.filter((patient) => {
            const name = (patient.name || "").toLowerCase();
            const identifier = (patient.identifier || "").toLowerCase();
            const label = (patient.label || "").toLowerCase();
            const combined = `${identifier} ${name} ${label}`;

            if (combined.includes(keyword) || name.includes(keyword) || identifier.includes(keyword)) {
                return true;
            }

            return tokens.length > 0 && tokens.every((token) => combined.includes(token));
        });
    }, [patientOptions, patientSearch]);

    const selectPatient = (patient) => {
        setFormData((previous) => ({
            ...previous,

            contact_number: patient.contact_number || previous.contact_number || "",
            student_id:
                patient.type === "student"
                    ? patient.id
                    : "",


            faculty_id:
                patient.type === "faculty"
                    ? patient.id
                    : "",


            staff_id:
                patient.type === "staff"
                    ? patient.id
                    : "",

        }));

        setPatientSearch(
            `${patient.identifier} - ${patient.name}`
        );

        setShowPatientDropdown(false);
    };









    const filteredMedicines = useMemo(() => {
        const keyword = medicineSearch
            .toLowerCase()
            .trim();
        const selectedTreatment = (formData.treatment || "").toLowerCase().trim();

        const filtered = medicines.filter((medicine) => {
            const medicineTreatment = getMedicineTreatment(medicine).toLowerCase();
            const name = getMedicineName(medicine).toLowerCase();
            const unit = getMedicineUnit(medicine).toLowerCase();

            return (
                !keyword ||
                name.includes(keyword) ||
                unit.includes(keyword) ||
                medicineTreatment.includes(keyword)
            );
        });

        if (selectedTreatment) {
            return [...filtered].sort((a, b) => {
                const aTreatment = getMedicineTreatment(a).toLowerCase();
                const bTreatment = getMedicineTreatment(b).toLowerCase();
                const aMatch = aTreatment === selectedTreatment || aTreatment === "all";
                const bMatch = bTreatment === selectedTreatment || bTreatment === "all";
                if (aMatch && !bMatch) return -1;
                if (!aMatch && bMatch) return 1;
                return 0;
            });
        }

        return filtered;
    }, [medicines, medicineSearch, formData.treatment]);

    const selectedMedicine = useMemo(() => {
        return medicines.find(
            (medicine) =>
                String(medicine.id) ===
                String(formData.medicine_id)
        );
    }, [
        medicines,
        formData.medicine_id,
    ]);

    const selectMedicine = (medicine) => {
        setFormData((previous) => ({
            ...previous,
            medicine_id: medicine.id,
            medicine_quantity: previous.medicine_quantity && Number(previous.medicine_quantity) >= 1 ? previous.medicine_quantity : "1",
        }));

        setMedicineSearch(
            getMedicineName(medicine)
        );

        setShowMedicineDropdown(false);
    };







    const fetchVisits = useCallback(async (page = 1, searchTerm = "") => {
        try {
            const normalizedSearch = searchTerm.trim();
            const response = await getCachedRecord(
                `clinic-visits:list:${page}:${normalizedSearch}`,
                () => api.get("/clinic-visits", {
                    params: {
                        page,
                        per_page: 25,
                        search: normalizedSearch || undefined,
                    },
                })
            );

            setVisits(
                Array.isArray(response.data?.data)
                    ? response.data.data
                    : Array.isArray(response.data)
                      ? response.data
                      : []
            );
            setVisitPagination({
                current_page: response.data?.current_page || 1,
                last_page: response.data?.last_page || 1,
                total: response.data?.total || 0,
                per_page: response.data?.per_page || 25,
            });
        } catch (error) {
            console.error(
                "Error fetching clinic visits:",
                error
            );

            setVisits([]);
            setVisitPagination({
                current_page: 1,
                last_page: 1,
                total: 0,
                per_page: 25,
            });
        }
    }, []);

    const fetchOptions = async () => {
        try {
            const response = await getCachedRecord(
                "clinic-visit-options:all",
                () => api.get("/clinic-visit-options")
            );
            const options = response.data || {};
            setStudents(normalizeData(options.students));
            setStaff(normalizeData(options.staff));
            setFaculties(normalizeData(options.faculties));
            setNurses(normalizeData(options.nurses));
            setMedicines(normalizeData(options.medicines));
            setStudentTotal(Number(options.totals?.students || 0));
            setFacultyStaffTotal(Number(options.totals?.faculties || 0) + Number(options.totals?.staff || 0));
        } catch (error) {
            console.error("Error fetching clinic visit options:", error);
            setStudents([]);
            setStaff([]);
            setFaculties([]);
            setNurses([]);
            setMedicines([]);
            setStudentTotal(0);
            setFacultyStaffTotal(0);
        }
    };

    const fetchMedicines = async () => {
        try {
            const response = await getCachedRecord(
                "clinic-visit-options:medicines",
                () => api.get("/clinic-visit-options", {
                    params: { medicine_search: "" },
                })
            );
            setMedicines(normalizeData(response.data?.medicines));
        } catch (error) {
            console.error(
                "Error fetching medicines:",
                error
            );

            setMedicines([]);
        }
    };

    const fetchAllData = async () => {
        setPageLoading(true);

        await Promise.all([
            fetchVisits(),
            fetchOptions(),
        ]);

        setPageLoading(false);
    };

    useEffect(() => {
        const timeout = setTimeout(() => fetchAllData(), 0);

        return () => clearTimeout(timeout);
    }, []);

    const initialVisitSearch = useRef(true);
    useEffect(() => {
        if (initialVisitSearch.current) {
            initialVisitSearch.current = false;
            return;
        }

        const timeout = setTimeout(() => {
            fetchVisits(1, search);
        }, 250);

        return () => clearTimeout(timeout);
    }, [search, fetchVisits]);

    useEffect(() => {
        const term = patientSearch.trim();

        if (!showPatientDropdown) {
            return undefined;
        }

        let cancelled = false;
        const timeout = setTimeout(async () => {
            try {
                const response = await getCachedRecord(
                    `clinic-visit-options:patients:${term.toLowerCase()}`,
                    () => api.get("/clinic-visit-options", {
                        params: term ? { patient_search: term } : {},
                    })
                );

                if (!cancelled) {
                    setStudents(normalizeData(response.data?.students));
                    setStaff(normalizeData(response.data?.staff));
                    setFaculties(normalizeData(response.data?.faculties));
                }
            } catch (error) {
                if (!cancelled) {
                    console.error("Patient option search error:", error);
                    setStudents([]);
                    setStaff([]);
                    setFaculties([]);
                }
            }
        }, 200);

        return () => {
            cancelled = true;
            clearTimeout(timeout);
        };
    }, [patientSearch, showPatientDropdown]);

    useEffect(() => {
        const term = medicineSearch.trim();

        if (!showMedicineDropdown) {
            return undefined;
        }

        let cancelled = false;
        const timeout = setTimeout(async () => {
            try {
                const response = await getCachedRecord(
                    `clinic-visit-options:medicine-search:${term.toLowerCase()}`,
                    () => api.get("/clinic-visit-options", {
                        params: term ? { medicine_search: term } : {},
                    })
                );

                if (!cancelled) {
                    setMedicines(normalizeData(response.data?.medicines));
                }
            } catch (error) {
                if (!cancelled) {
                    console.error("Medicine option search error:", error);
                    setMedicines([]);
                }
            }
        }, 200);

        return () => {
            cancelled = true;
            clearTimeout(timeout);
        };
    }, [medicineSearch, showMedicineDropdown]);







    useEffect(() => {
        const handleMedicineStockUpdated = () => {
            fetchMedicines();
        };

        window.addEventListener(
            "medicine-stock-updated",
            handleMedicineStockUpdated
        );

        return () => {
            window.removeEventListener(
                "medicine-stock-updated",
                handleMedicineStockUpdated
            );
        };
    }, []);

    useEffect(() => {
        if (!showForm) return;

        const handleKeyDown = (event: KeyboardEvent) => {
            if (event.key === "Escape" && !loading && !showAddConfirmation) {
                closeForm();
            }
        };

        const originalOverflow = document.body.style.overflow;
        document.body.style.overflow = "hidden";

        document.addEventListener("keydown", handleKeyDown);
        return () => {
            document.body.style.overflow = originalOverflow;
            document.removeEventListener("keydown", handleKeyDown);
        };
    }, [showForm, loading, showAddConfirmation]);







    const handleChange = (event) => {
        const { name, value } = event.target;

        setFormData((previous) => ({
            ...previous,
            [name]: value,
        }));
    };

    const handleTreatmentChange = (event) => {
        const value = event.target.value;








        setFormData((previous) => ({
            ...previous,
            treatment: value,
            medicine_id: "",
            medicine_quantity: "",
        }));

        setMedicineSearch("");
        setShowMedicineDropdown(false);
    };







    const openAddForm = () => {
        setEditingVisit(null);

        const storedUser = (() => {
            try {
                const u = localStorage.getItem("user");
                return u ? JSON.parse(u) : null;
            } catch {
                return null;
            }
        })();

        const defaultNurseId =
            (storedUser?.id && nurses.some((n) => Number(n.id) === Number(storedUser.id)))
                ? storedUser.id
                : nurses[0]?.id || "";

        setFormData({
            ...EMPTY_FORM,
            nurse_id: defaultNurseId,
            visit_date: getToday(),
        });

        setPatientSearch("");
        setMedicineSearch("");

        setShowPatientDropdown(false);
        setShowMedicineDropdown(false);

        setShowForm(true);
    };







    const handleEdit = (visit) => {
        setEditingVisit(visit);

        const treatment = visit.treatment || "";
        const contact =
            visit.contact_number ||
            visit.student?.contact_number ||
            visit.faculty?.contact_number ||
            visit.staff?.contact_number ||
            "";

        setFormData({
            student_id: visit.student_id || "",
            faculty_id: visit.faculty_id || "",
            staff_id: visit.staff_id || "",
            contact_number: contact,
            is_emergency: Boolean(visit.is_emergency),
            send_sms: true,

            nurse_id:
                visit.nurse_id ||
                visit.nurse?.id ||
                "",

            visit_date: formatDateInput(
                visit.visit_date
            ),

            reason: visit.reason || "",

            symptoms: visit.symptoms || "",

            temperature:
                visit.temperature || "",

            blood_pressure:
                visit.blood_pressure || "",

            assessment:
                visit.assessment || "",

            treatment: treatment,

            medicine_id:
                visit.medicine_id ||
                visit.medicine?.id ||
                "",

            medicine_quantity:
                visit.medicine_quantity || "",

            remarks: visit.remarks || "",
        });

        if (visit.student) {
            setPatientSearch(
                `${visit.student.student_id || ""} - ${getFullName(
                    visit.student
                )}`
            );
        } else if (visit.faculty) {
            setPatientSearch(
                `${visit.faculty.employee_id || ""} - ${getFullName(
                    visit.faculty
                )}`
            );
        } else if (visit.staff) {
            setPatientSearch(
                `${visit.staff.staff_id || ""} - ${getFullName(
                    visit.staff
                )}`
            );
        } else {
            setPatientSearch("");
        }

        setMedicineSearch(
            getMedicineName(visit.medicine)
        );

        setShowPatientDropdown(false);
        setShowMedicineDropdown(false);

        setShowForm(true);
    };







    const persistVisit = async (payload, visitToUpdate = null) => {
        setLoading(true);

        try {
            if (visitToUpdate) {
                await api.put(
                    `/clinic-visits/${visitToUpdate.id}`,
                    payload
                );
                setToastMessage("Clinic visit updated successfully.");
            } else {
                await api.post("/clinic-visits", payload);
                setToastMessage("Clinic visit added successfully.");
            }

            await Promise.all([
                fetchVisits(
                    visitToUpdate ? visitPagination.current_page : 1,
                    search
                ),
            ]);

            window.dispatchEvent(new Event("medicine-stock-updated"));
            setShowForm(false);
            setEditingVisit(null);
            setFormData({ ...EMPTY_FORM });
            setPatientSearch("");
            setMedicineSearch("");
            setShowPatientDropdown(false);
            setShowMedicineDropdown(false);

            addClinicNotification({
                type: visitToUpdate ? "edit" : "add",
                title: visitToUpdate ? "Clinic Visit Updated" : "Clinic Visit Added",
                description: `Visit on ${payload.visit_date || "today"} ${visitToUpdate ? "updated" : "recorded"}`,
                category: "visit",
            });
        } catch (error) {
            console.error("Error saving clinic visit:", error);
            alert(
                getErrorMessage(
                    error,
                    "Failed to save clinic visit."
                )
            );
        } finally {
            setLoading(false);
        }
    };

    const cancelAddConfirmation = () => {
        if (loading) return;
        setShowAddConfirmation(false);
        setPendingVisitPayload(null);
    };

    const confirmAddVisit = async () => {
        if (!pendingVisitPayload || loading) return;

        const payload = pendingVisitPayload;
        setShowAddConfirmation(false);
        setPendingVisitPayload(null);
        await persistVisit(payload);
    };

    const handleSubmit = async (event) => {
        event.preventDefault();

        if (loading) {
            return;
        }

        const patientCount = [
            formData.student_id,
            formData.faculty_id,
            formData.staff_id,
        ].filter(Boolean).length;

        if (patientCount === 0) {
            alert(
                "Please select a Student, Faculty, or Staff."
            );
            return;
        }

        if (patientCount > 1) {
            alert(
                "Please select only one patient."
            );
            return;
        }

        if (!formData.nurse_id) {
            alert(
                "Please select a nurse on duty."
            );
            return;
        }

        if (!formData.visit_date) {
            alert(
                "Please select the visit date."
            );
            return;
        }

        if (!formData.reason.trim()) {
            alert(
                "Please enter the reason for visit."
            );
            return;
        }

        if (
            treatmentNeedsMedicine(formData.treatment) &&
            !formData.medicine_id
        ) {
            alert(
                `Please select a medicine / supply for ${formData.treatment}.`
            );
            return;
        }

        if (
            formData.medicine_id &&
            (
                !formData.medicine_quantity ||
                Number(
                    formData.medicine_quantity
                ) < 1
            )
        ) {
            alert(
                "Please enter a valid quantity used (at least 1)."
            );
            return;
        }

        if (
            !editingVisit &&
            selectedMedicine &&
            Number(
                formData.medicine_quantity
            ) >
                getMedicineStock(
                    selectedMedicine
                )
        ) {
            alert(
                `Not enough stock. Available stock: ${getMedicineStock(
                    selectedMedicine
                )}.`
            );
            return;
        }

        const payload = {
            student_id:
                formData.student_id || null,

            faculty_id:
                formData.faculty_id || null,

            staff_id:
                formData.staff_id || null,

            nurse_id:
                formData.nurse_id || null,

            contact_number:
                formData.contact_number?.trim() || null,

            is_emergency:
                Boolean(formData.is_emergency),

            send_sms:
                Boolean(formData.send_sms !== false),

            visit_date:
                formData.visit_date,

            reason:
                formData.reason.trim(),

            symptoms:
                formData.symptoms.trim() ||
                null,

            temperature:
                formData.temperature.trim() ||
                null,

            blood_pressure:
                formData.blood_pressure.trim() ||
                null,

            assessment:
                formData.assessment.trim() ||
                null,

            treatment:
                formData.treatment || null,

            medicine_id:
                formData.medicine_id || null,

            medicine_quantity:
                formData.medicine_id
                    ? Number(
                          formData.medicine_quantity
                      ) || 1
                    : null,

            remarks:
                formData.remarks.trim() ||
                null,
        };

        if (editingVisit) {
            await persistVisit(payload, editingVisit);
        } else {
            setPendingVisitPayload(payload);
            setShowAddConfirmation(true);
        }
    };







    const handleDelete = async () => {
        if (!deleteVisit) {
            return;
        }

        try {
            await api.delete(
                `/clinic-visits/${deleteVisit.id}`
            );

            setToastMessage("Clinic visit deleted successfully.");

            const patient = getPatientName(deleteVisit);
            addClinicNotification({
                type: "delete",
                title: "Clinic Visit Deleted",
                description: `Visit record for ${patient.name || "Patient"} removed`,
                category: "visit",
            });

            await Promise.all([
                fetchVisits(
                    visits.length === 1 && visitPagination.current_page > 1
                        ? visitPagination.current_page - 1
                        : visitPagination.current_page,
                    search
                ),
            ]);

            window.dispatchEvent(
                new Event(
                    "medicine-stock-updated"
                )
            );
        } catch (error) {
            console.error(
                "Error deleting clinic visit:",
                error
            );

            alert(
                getErrorMessage(
                    error,
                    "Failed to delete clinic visit."
                )
            );
        } finally {
            setDeleteVisit(null);
        }
    };







    const closeForm = () => {
        if (loading) {
            return;
        }

        setShowForm(false);
        setEditingVisit(null);

        setFormData({
            ...EMPTY_FORM,
        });

        setPatientSearch("");
        setMedicineSearch("");

        setShowPatientDropdown(false);
        setShowMedicineDropdown(false);
    };







    const filteredVisits = useMemo(() => {
        const keyword = search.toLowerCase().trim();
        const normalizedDate = normalizeVisitDateSearch(keyword);
        const tokens = keyword.split(/[\s,]+/).filter((t) => t && t !== "-");

        const now = new Date();
        const todayStr = new Date(now.getTime() - now.getTimezoneOffset() * 60000).toISOString().slice(0, 10);
        const sevenDaysAgo = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000).toISOString().slice(0, 10);
        const thirtyDaysAgo = new Date(now.getTime() - 30 * 24 * 60 * 60 * 1000).toISOString().slice(0, 10);
        const thisMonthPrefix = todayStr.slice(0, 7);

        return (visits || [])
            .filter((visit) => {
                const patient = getPatientName(visit);
                const pType = (patient.type || "").toLowerCase();

                // 1. Patient Type Filter
                if (patientTypeFilter !== "all" && pType !== patientTypeFilter.toLowerCase()) {
                    return false;
                }

                // 2. Treatment Filter
                if (treatmentFilter !== "all" && (visit.treatment || "").toLowerCase() !== treatmentFilter.toLowerCase()) {
                    return false;
                }

                // 3. Date Range Filter
                if (dateFilter !== "all") {
                    const rawDate = String(visit.visit_date || "").slice(0, 10);
                    if (!rawDate) return false;
                    if (dateFilter === "today" && rawDate !== todayStr) return false;
                    if (dateFilter === "week" && rawDate < sevenDaysAgo) return false;
                    if (dateFilter === "month" && !rawDate.startsWith(thisMonthPrefix)) return false;
                    if (dateFilter === "30days" && rawDate < thirtyDaysAgo) return false;
                }

                // 4. Keyword search
                if (!keyword) return true;

                const nurseName = getNurseName(visit.nurse);
                const medicineName = getMedicineName(visit.medicine);
                const fullContent = `
                    ${patient.id || ""}
                    ${patient.name || ""}
                    ${patient.type || ""}
                    ${nurseName}
                    ${visit.reason || ""}
                    ${visit.symptoms || ""}
                    ${visit.assessment || ""}
                    ${visit.treatment || ""}
                    ${medicineName}
                    ${visit.medicine_quantity || ""}
                    ${visit.visit_date || ""}
                    ${formatDate(visit.visit_date)}
                    ${visit.temperature || ""}
                    ${visit.blood_pressure || ""}
                    ${visit.remarks || ""}
                `.toLowerCase();

                if (fullContent.includes(keyword)) return true;
                if (tokens.length > 0 && tokens.every((token) => fullContent.includes(token))) return true;
                if (normalizedDate && String(visit.visit_date || "").slice(0, 10) === normalizedDate) return true;

                return false;
            })
            .sort((a, b) => {
                if (sortBy === "date_desc") {
                    const dateA = String(a.visit_date || "");
                    const dateB = String(b.visit_date || "");
                    return dateB.localeCompare(dateA);
                }
                if (sortBy === "date_asc") {
                    const dateA = String(a.visit_date || "");
                    const dateB = String(b.visit_date || "");
                    return dateA.localeCompare(dateB);
                }
                if (sortBy === "name_asc") {
                    const nameA = (getPatientName(a).name || "").toLowerCase();
                    const nameB = (getPatientName(b).name || "").toLowerCase();
                    return nameA.localeCompare(nameB);
                }
                if (sortBy === "name_desc") {
                    const nameA = (getPatientName(a).name || "").toLowerCase();
                    const nameB = (getPatientName(b).name || "").toLowerCase();
                    return nameB.localeCompare(nameA);
                }
                return 0;
            });
    }, [visits, search, patientTypeFilter, dateFilter, treatmentFilter, sortBy]);







    const handleSendSms = async (visit: VisitRecord) => {
        const patient = getPatientName(visit);
        const recipient = patient.contact_number || visit.contact_number;

        if (!recipient) {
            alert("No cellphone number registered for this visit.");
            return;
        }

        const isEmergency = Boolean(visit.is_emergency);
        const confirmMsg = `Send SMS notification to ${patient.name} (${recipient})?\n\nTemplate:\nTCC CLINIC\nDATE: ${formatDate(visit.visit_date)}\nGreetings!\n${isEmergency ? '[EMERGENCY MEDICAL ADVISORY]' : 'Clinic Visit Notice'}`;

        if (!window.confirm(confirmMsg)) {
            return;
        }

        try {
            const response = await api.post(`/clinic-visits/${visit.id}/send-sms`, {
                is_emergency: isEmergency,
            });
            setToastMessage(response.data?.message || "SMS notification dispatched successfully.");
            await fetchVisits(visitPagination.current_page, search);
        } catch (error: any) {
            const errorMsg = error.response?.data?.message || "Failed to dispatch SMS.";
            alert(errorMsg);
        }
    };

    return (
        <div className="tcc-module-page min-h-screen px-5 py-5 lg:px-6 lg:py-6">
            <FloatingToast
                message={toastMessage}
                onClose={() => setToastMessage("")}
            />
            <div className="mx-auto max-w-[1500px]">


                <div className="tcc-module-header mb-5 rounded-2xl border px-5 py-5 shadow-sm">
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

                        <div className="flex items-center gap-4">
                            <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-[#fcebe7] text-[#70091f]">
                                <Stethoscope size={24} />
                            </div>

                            <div>
                                <p className="text-[10px] font-bold uppercase tracking-[0.16em] text-[#70091f]">
                                    Clinic Management
                                </p>

                                <h1 className="text-2xl font-bold tracking-tight text-[#3d3329]">
                                    Clinic Visits
                                </h1>

                                <p className="mt-1 text-xs text-[#887d70]">
                                    View and manage student,
                                    faculty, and staff
                                    clinic visit records.
                                </p>
                            </div>
                        </div>

                        <button
                            type="button"
                            onClick={openAddForm}
                            className="flex items-center justify-center gap-2 rounded-xl bg-[#70091f] px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-[#8b1505]"
                        >
                            <Plus size={17} />
                            Add Clinic Visit
                        </button>
                    </div>
                </div>


                <div className="mb-5 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">

                    <div className="rounded-xl border border-[#e8dfd4] bg-white p-4 shadow-sm">
                        <div className="flex items-center gap-3">
                            <div className="flex h-10 w-10 items-center justify-center rounded-full bg-[#fcebe7] text-[#70091f]">
                                <Stethoscope size={19} />
                            </div>

                            <div>
                                <p className="text-xs text-[#887d70]">
                                    Total Visits
                                </p>

                                <p className="text-2xl font-bold text-[#3c332a]">
                                    {visitPagination.total}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div className="rounded-xl border border-[#e8dfd4] bg-white p-4 shadow-sm">
                        <div className="flex items-center gap-3">
                            <div className="flex h-10 w-10 items-center justify-center rounded-full bg-amber-50 text-amber-600">
                                <UserRound size={19} />
                            </div>

                            <div>
                                <p className="text-xs text-[#887d70]">
                                    Students
                                </p>

                                <p className="text-2xl font-bold text-[#3c332a]">
                                    {studentTotal}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div className="rounded-xl border border-[#e8dfd4] bg-white p-4 shadow-sm">
                        <div className="flex items-center gap-3">
                            <div className="flex h-10 w-10 items-center justify-center rounded-full bg-amber-50 text-amber-600">
                                <UserRound size={19} />
                            </div>

                            <div>
                                <p className="text-xs text-[#887d70]">
                                    Faculty & Staff
                                </p>

                                <p className="text-2xl font-bold text-[#3c332a]">
                                    {facultyStaffTotal}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div className="rounded-xl border border-[#e8dfd4] bg-white p-4 shadow-sm">
                        <div className="flex items-center gap-3">
                            <div className="flex h-10 w-10 items-center justify-center rounded-full bg-green-50 text-green-600">
                                <Activity size={19} />
                            </div>

                            <div>
                                <p className="text-xs text-[#887d70]">
                                    Nurses
                                </p>

                                <p className="text-2xl font-bold text-[#3c332a]">
                                    {nurses.length}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>


                <ClinicVisitModal
                    showForm={showForm}
                    closeForm={closeForm}
                    editingVisit={editingVisit}
                    formData={formData}
                    handleChange={handleChange}
                    handleSubmit={handleSubmit}
                    loading={loading}
                    patientSearch={patientSearch}
                    setPatientSearch={setPatientSearch}
                    showPatientDropdown={showPatientDropdown}
                    setShowPatientDropdown={setShowPatientDropdown}
                    filteredPatients={filteredPatients}
                    selectPatient={selectPatient}
                    nurses={nurses}
                    getNurseName={getNurseName}
                    getToday={getToday}
                    setFormData={setFormData}
                    handleTreatmentChange={handleTreatmentChange}
                    treatments={TREATMENTS}
                    treatmentNeedsMedicine={treatmentNeedsMedicine}
                    medicineSearch={medicineSearch}
                    setMedicineSearch={setMedicineSearch}
                    showMedicineDropdown={showMedicineDropdown}
                    setShowMedicineDropdown={setShowMedicineDropdown}
                    filteredMedicines={filteredMedicines}
                    selectMedicine={selectMedicine}
                    getMedicineName={getMedicineName}
                    getMedicineUnit={getMedicineUnit}
                    getMedicineStock={getMedicineStock}
                    getMedicineTreatment={getMedicineTreatment}
                    selectedMedicine={selectedMedicine}
                />

                {/* Search and Filters Controls */}
                <div className="mb-6 rounded-2xl border border-[#e8dfd4] bg-white p-4 shadow-sm sm:p-5 dark:border-[#4e4234] dark:bg-[#211c17]">
                    <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-12">
                        {/* Search Input */}
                        <div className="relative sm:col-span-2 md:col-span-4">
                            <label htmlFor="visit-search" className="sr-only">
                                Search clinic visits
                            </label>
                            <Search
                                size={18}
                                aria-hidden="true"
                                className="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-[#a99d8f] dark:text-[#887d70]"
                            />
                            <input
                                id="visit-search"
                                type="text"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Search by ID, name, reason, medicine, date..."
                                autoComplete="off"
                                className="w-full rounded-xl border border-[#e8dfd4] bg-[#fcfaf6] py-2.5 pl-10 pr-10 text-sm text-[#3d3329] outline-none transition placeholder:text-[#a99d8f] focus:border-[#70091f] focus:bg-white focus:ring-4 focus:ring-[#70091f]/10 dark:border-[#4e4234] dark:bg-[#261f19] dark:text-[#f8f3eb] dark:placeholder:text-[#887d70] dark:focus:border-[#70091f] dark:focus:bg-[#211c17]"
                            />
                            {search && (
                                <button
                                    type="button"
                                    onClick={() => setSearch("")}
                                    aria-label="Clear search"
                                    className="absolute right-2.5 top-1/2 flex h-6 w-6 -translate-y-1/2 items-center justify-center rounded-md text-[#a99d8f] hover:bg-[#f6f1e9] hover:text-[#3c332a] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#70091f] dark:text-[#887d70] dark:hover:bg-[#382f25] dark:hover:text-[#f8f3eb]"
                                >
                                    <X size={15} aria-hidden="true" />
                                </button>
                            )}
                        </div>

                        {/* Patient Type Filter */}
                        <div className="md:col-span-2">
                            <label htmlFor="patient-type-filter" className="sr-only">
                                Filter by Patient Type
                            </label>
                            <select
                                id="patient-type-filter"
                                value={patientTypeFilter}
                                onChange={(e) => setPatientTypeFilter(e.target.value)}
                                aria-label="Filter by Patient Type"
                                className="w-full rounded-xl border border-[#e8dfd4] bg-[#fcfaf6] px-3.5 py-2.5 text-sm font-medium text-[#3d3329] outline-none transition focus:border-[#70091f] focus:bg-white focus:ring-4 focus:ring-[#70091f]/10 dark:border-[#4e4234] dark:bg-[#261f19] dark:text-[#f8f3eb] dark:focus:border-[#70091f] dark:focus:bg-[#211c17]"
                            >
                                <option value="all">All Patients</option>
                                <option value="student">Students</option>
                                <option value="faculty">Faculty</option>
                                <option value="staff">Staff</option>
                            </select>
                        </div>

                        {/* Date Filter */}
                        <div className="md:col-span-2">
                            <label htmlFor="date-filter" className="sr-only">
                                Filter by Date
                            </label>
                            <select
                                id="date-filter"
                                value={dateFilter}
                                onChange={(e) => setDateFilter(e.target.value)}
                                aria-label="Filter by Date"
                                className="w-full rounded-xl border border-[#e8dfd4] bg-[#fcfaf6] px-3.5 py-2.5 text-sm font-medium text-[#3d3329] outline-none transition focus:border-[#70091f] focus:bg-white focus:ring-4 focus:ring-[#70091f]/10 dark:border-[#4e4234] dark:bg-[#261f19] dark:text-[#f8f3eb] dark:focus:border-[#70091f] dark:focus:bg-[#211c17]"
                            >
                                <option value="all">All Time</option>
                                <option value="today">Today</option>
                                <option value="week">This Week</option>
                                <option value="month">This Month</option>
                                <option value="30days">Past 30 Days</option>
                            </select>
                        </div>

                        {/* Treatment Filter */}
                        <div className="md:col-span-2">
                            <label htmlFor="treatment-filter" className="sr-only">
                                Filter by Treatment
                            </label>
                            <select
                                id="treatment-filter"
                                value={treatmentFilter}
                                onChange={(e) => setTreatmentFilter(e.target.value)}
                                aria-label="Filter by Treatment"
                                className="w-full rounded-xl border border-[#e8dfd4] bg-[#fcfaf6] px-3.5 py-2.5 text-sm font-medium text-[#3d3329] outline-none transition focus:border-[#70091f] focus:bg-white focus:ring-4 focus:ring-[#70091f]/10 dark:border-[#4e4234] dark:bg-[#261f19] dark:text-[#f8f3eb] dark:focus:border-[#70091f] dark:focus:bg-[#211c17]"
                            >
                                <option value="all">All Treatments</option>
                                {TREATMENTS.map((treatment) => (
                                    <option key={treatment} value={treatment}>
                                        {treatment}
                                    </option>
                                ))}
                            </select>
                        </div>

                        {/* Sort Filter */}
                        <div className="md:col-span-2">
                            <label htmlFor="sort-filter" className="sr-only">
                                Sort visits
                            </label>
                            <select
                                id="sort-filter"
                                value={sortBy}
                                onChange={(e) => setSortBy(e.target.value)}
                                aria-label="Sort visits"
                                className="w-full rounded-xl border border-[#e8dfd4] bg-[#fcfaf6] px-3.5 py-2.5 text-sm font-medium text-[#3d3329] outline-none transition focus:border-[#70091f] focus:bg-white focus:ring-4 focus:ring-[#70091f]/10 dark:border-[#4e4234] dark:bg-[#261f19] dark:text-[#f8f3eb] dark:focus:border-[#70091f] dark:focus:bg-[#211c17]"
                            >
                                <option value="date_desc">Newest First</option>
                                <option value="date_asc">Oldest First</option>
                                <option value="name_asc">Patient Name (A-Z)</option>
                                <option value="name_desc">Patient Name (Z-A)</option>
                            </select>
                        </div>
                    </div>

                    {/* Active filter summary bar & Reset */}
                    {hasActiveFilters && (
                        <div className="mt-4 flex flex-wrap items-center justify-between gap-2 border-t border-[#f0e7dd] pt-3 text-xs text-[#887d70] dark:border-[#382f25] dark:text-[#c4b5a3]">
                            <div className="flex flex-wrap items-center gap-1.5">
                                <span className="font-semibold text-[#3d3329] dark:text-[#f8f3eb]">
                                    Active filters:
                                </span>
                                {search.trim() && (
                                    <span className="inline-flex items-center gap-1 rounded-full bg-[#fcebe7] px-2.5 py-0.5 font-medium text-[#675948] dark:bg-[#382f25] dark:text-[#ded4c9]">
                                        Keyword: "{search.trim()}"
                                        <button
                                            type="button"
                                            onClick={() => setSearch("")}
                                            aria-label="Remove search filter"
                                            className="text-[#887d70] hover:text-[#3d3329] dark:hover:text-[#f8f3eb]"
                                        >
                                            <X size={12} />
                                        </button>
                                    </span>
                                )}
                                {patientTypeFilter !== "all" && (
                                    <span className="inline-flex items-center gap-1 rounded-full bg-[#fcebe7] px-2.5 py-0.5 font-medium text-[#675948] dark:bg-[#382f25] dark:text-[#ded4c9]">
                                        Type: {patientTypeFilter}
                                        <button
                                            type="button"
                                            onClick={() => setPatientTypeFilter("all")}
                                            aria-label="Remove patient type filter"
                                            className="text-[#887d70] hover:text-[#3d3329] dark:hover:text-[#f8f3eb]"
                                        >
                                            <X size={12} />
                                        </button>
                                    </span>
                                )}
                                {dateFilter !== "all" && (
                                    <span className="inline-flex items-center gap-1 rounded-full bg-[#fcebe7] px-2.5 py-0.5 font-medium text-[#675948] dark:bg-[#382f25] dark:text-[#ded4c9]">
                                        Date: {dateFilter === "today" ? "Today" : dateFilter === "week" ? "This Week" : dateFilter === "month" ? "This Month" : "Past 30 Days"}
                                        <button
                                            type="button"
                                            onClick={() => setDateFilter("all")}
                                            aria-label="Remove date filter"
                                            className="text-[#887d70] hover:text-[#3d3329] dark:hover:text-[#f8f3eb]"
                                        >
                                            <X size={12} />
                                        </button>
                                    </span>
                                )}
                                {treatmentFilter !== "all" && (
                                    <span className="inline-flex items-center gap-1 rounded-full bg-[#fcebe7] px-2.5 py-0.5 font-medium text-[#675948] dark:bg-[#382f25] dark:text-[#ded4c9]">
                                        Treatment: {treatmentFilter}
                                        <button
                                            type="button"
                                            onClick={() => setTreatmentFilter("all")}
                                            aria-label="Remove treatment filter"
                                            className="text-[#887d70] hover:text-[#3d3329] dark:hover:text-[#f8f3eb]"
                                        >
                                            <X size={12} />
                                        </button>
                                    </span>
                                )}
                                {sortBy !== "date_desc" && (
                                    <span className="inline-flex items-center gap-1 rounded-full bg-[#fcebe7] px-2.5 py-0.5 font-medium text-[#675948] dark:bg-[#382f25] dark:text-[#ded4c9]">
                                        Sort: {sortBy === "date_asc" ? "Oldest First" : sortBy === "name_asc" ? "Patient Name (A-Z)" : "Patient Name (Z-A)"}
                                        <button
                                            type="button"
                                            onClick={() => setSortBy("date_desc")}
                                            aria-label="Reset sort"
                                            className="text-[#887d70] hover:text-[#3d3329] dark:hover:text-[#f8f3eb]"
                                        >
                                            <X size={12} />
                                        </button>
                                    </span>
                                )}
                                <span className="ml-1 text-[#887d70]">
                                    ({filteredVisits.length} {filteredVisits.length === 1 ? "record" : "records"} found)
                                </span>
                            </div>
                            <button
                                type="button"
                                onClick={resetAllFilters}
                                className="font-semibold text-[#70091f] hover:underline dark:text-[#c4a07d]"
                            >
                                Reset all filters
                            </button>
                        </div>
                    )}
                </div>

                <ClinicVisitTable search={search} setSearch={setSearch} loading={loading} pageLoading={pageLoading} filteredVisits={filteredVisits} getPatientName={getPatientName} getNurseName={getNurseName} getMedicineName={getMedicineName} getMedicineUnit={getMedicineUnit} formatDate={formatDate} handleEdit={handleEdit} deleteVisit={deleteVisit} setDeleteVisit={setDeleteVisit} handleDelete={handleDelete} visitPagination={visitPagination} fetchVisits={fetchVisits} onSendSms={handleSendSms} />

                {showAddConfirmation && (
                    <div
                        className="fixed inset-0 z-[120] flex items-center justify-center bg-stone-950/45 px-4 py-6 backdrop-blur-sm"
                        onMouseDown={(event) => {
                            if (event.target === event.currentTarget) {
                                cancelAddConfirmation();
                            }
                        }}
                    >
                        <section
                            role="alertdialog"
                            aria-modal="true"
                            aria-labelledby="confirm-add-visit-title"
                            aria-describedby="confirm-add-visit-description"
                            className="w-full max-w-md rounded-2xl border border-[#e8dfd4] bg-white p-6 shadow-2xl shadow-stone-950/20"
                        >
                            <div className="mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-[#fcebe7] text-[#70091f]">
                                <CircleHelp size={24} aria-hidden="true" />
                            </div>

                            <h2 id="confirm-add-visit-title" className="text-lg font-bold text-[#302820]">
                                Confirm clinic visit
                            </h2>
                            <p id="confirm-add-visit-description" className="mt-2 text-sm leading-6 text-[#766959]">
                                Are you sure you want to add this clinic visit?
                            </p>

                            <div className="mt-4 space-y-2 rounded-xl border border-[#e8dfd4] bg-[#fcfaf6] p-3 text-sm">
                                <p className="truncate text-[#302820]">
                                    <span className="font-semibold">Patient:</span>{" "}
                                    {patientSearch || "Selected patient"}
                                </p>
                                <p className="flex items-center gap-2 text-[#766959]">
                                    <CalendarDays size={15} aria-hidden="true" />
                                    <span><span className="font-semibold">Visit date:</span>{" "}{formatDate(formData.visit_date)}</span>
                                </p>
                            </div>

                            <div className="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                                <button
                                    type="button"
                                    onClick={cancelAddConfirmation}
                                    className="rounded-xl border border-[#e8dfd4] px-4 py-2.5 text-sm font-semibold text-[#766959] transition hover:bg-[#fcfaf6] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#70091f]/40"
                                >
                                    Go back
                                </button>
                                <button
                                    type="button"
                                    onClick={confirmAddVisit}
                                    className="rounded-xl bg-[#70091f] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#8b1505] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#70091f]/40"
                                >
                                    Yes, add visit
                                </button>
                            </div>
                        </section>
                    </div>
                )}
            </div>
        </div>
    );
}

export default ClinicVisits;
