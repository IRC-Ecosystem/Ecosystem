import AppShell from "@/components/AppShell";
import RolePage from "@/components/RolePage";
import AdminBurn from "@/components/dashboards/AdminBurn";

export default function AdminBurnPage() {
  return <AppShell><RolePage allowed={["ADMIN", "CENTRAL_BANK_ADMIN"]}><AdminBurn /></RolePage></AppShell>;
}