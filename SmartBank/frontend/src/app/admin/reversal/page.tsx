import AppShell from "@/components/AppShell";
import RolePage from "@/components/RolePage";
import AdminReversal from "@/components/dashboards/AdminReversal";

export default function AdminReversalPage() {
  return <AppShell><RolePage allowed={["ADMIN", "CENTRAL_BANK_ADMIN"]}><AdminReversal /></RolePage></AppShell>;
}
