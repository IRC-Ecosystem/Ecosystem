import AppShell from "@/components/AppShell";
import RolePage from "@/components/RolePage";
import AdminLedger from "@/components/dashboards/AdminLedger";

export default function AdminLedgerPage() {
  return <AppShell><RolePage allowed={["ADMIN", "CENTRAL_BANK_ADMIN"]}><AdminLedger /></RolePage></AppShell>;
}
